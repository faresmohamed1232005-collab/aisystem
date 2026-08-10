<?php

namespace App\Services\Diagnostics;

use App\Services\SqliteBackupService;
use App\Services\Sync\SyncPullService;
use App\Services\Sync\SyncRunner;
use App\Services\Sync\SyncStatus;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class RecoveryService
{
    public const DISCONNECT_PHRASE = 'فصل وإعادة إعداد الفرع';
    public const FACTORY_RESET_PHRASE = 'إعادة ضبط المصنع نهائياً';

    public function __construct(
        private SqliteBackupService $backups,
        private OwnerVerificationService $owners,
    ) {}

    public function backup(string $reason = 'manual', array $metadata = []): array
    {
        return $this->backups->create(null, array_merge($this->identityMetadata($reason), $metadata));
    }

    public function repairSync(string $login, string $password, SyncPullService $pull, SyncRunner $runner): array
    {
        $this->ensureDesktopSqlite();
        $identity = $this->verifyCurrentOwner($login, $password);
        $backup = $this->backup('repair_sync_identity', [
            'verified_branch_id' => $identity['branch_id'],
            'verified_owner_uuid' => $identity['owner_uuid'],
            'cleared_sync_metadata' => $this->syncMetadataSnapshot('users'),
        ]);

        $oldOwnerUuid = Settings::get('branch.owner_uuid');
        $oldState = DB::table('sync_state')->where('table_name', 'users')->first();
        $oldProgress = Schema::hasTable('sync_table_progress')
            ? DB::table('sync_table_progress')->where('table_name', 'users')->where('direction', 'pull')->first()
            : null;

        $lock = Cache::lock('sync.runner', 300);
        if (! $lock->get()) {
            throw new RuntimeException('توجد مزامنة نشطة الآن. انتظر اكتمالها ثم أعد المحاولة.');
        }

        try {
            try {
                DB::transaction(function () use ($identity): void {
                    Settings::set('branch.owner_uuid', $identity['owner_uuid']);
                    DB::table('sync_state')->where('table_name', 'users')->delete();
                    if (Schema::hasTable('sync_table_progress')) {
                        DB::table('sync_table_progress')->where('table_name', 'users')->where('direction', 'pull')->delete();
                    }
                });

                $pull->pullStep('users');
            } catch (\Throwable $e) {
                DB::transaction(function () use ($oldOwnerUuid, $oldState, $oldProgress): void {
                    if ($oldOwnerUuid) {
                        Settings::set('branch.owner_uuid', $oldOwnerUuid);
                    } else {
                        Settings::forget('branch.owner_uuid');
                    }
                    DB::table('sync_state')->where('table_name', 'users')->delete();
                    if ($oldState) {
                        DB::table('sync_state')->insert((array) $oldState);
                    }
                    if (Schema::hasTable('sync_table_progress')) {
                        DB::table('sync_table_progress')->where('table_name', 'users')->where('direction', 'pull')->delete();
                        if ($oldProgress) {
                            DB::table('sync_table_progress')->insert((array) $oldProgress);
                        }
                    }
                });
                throw $e;
            }
        } finally {
            $lock->release();
        }

        // بعد إصلاح هوية المالك لا نتراجع عنها إذا تعطل جدول لاحق؛ النسخة الاحتياطية
        // موجودة، وحساب الدخول أصبح صالحاً ويمكن إعادة المزامنة الكاملة لاحقاً.
        $result = $runner->run(manual: true);

        return ['backup' => $backup, 'sync' => $result];
    }

    public function retryTable(string $table, SyncPullService $pull): array
    {
        if (! in_array($table, $pull->pullableTables(), true)) {
            throw new RuntimeException('الجدول المطلوب ليس ضمن قائمة السحب المسموحة.');
        }

        $oldState = DB::table('sync_state')->where('table_name', $table)->first();
        $oldProgress = Schema::hasTable('sync_table_progress')
            ? DB::table('sync_table_progress')->where('table_name', $table)->where('direction', 'pull')->first()
            : null;
        $backup = $this->backup('retry_pull_table', [
            'retry_table' => $table,
            'cleared_sync_metadata' => $this->syncMetadataSnapshot($table),
        ]);

        DB::transaction(function () use ($table): void {
            DB::table('sync_state')->where('table_name', $table)->delete();
            if (Schema::hasTable('sync_table_progress')) {
                DB::table('sync_table_progress')
                    ->where('table_name', $table)
                    ->where('direction', 'pull')
                    ->where('sync_mode', 'initial')
                    ->delete();
            }
        });

        try {
            return ['backup' => $backup, 'pull' => $pull->pullStep($table)];
        } catch (\Throwable $e) {
            // فشل أول طلب لا يجب أن يفقد cursor أو سياق التشخيص السابق.
            DB::transaction(function () use ($table, $oldState, $oldProgress): void {
                DB::table('sync_state')->where('table_name', $table)->delete();
                if ($oldState) {
                    DB::table('sync_state')->insert((array) $oldState);
                }
                if (Schema::hasTable('sync_table_progress')) {
                    DB::table('sync_table_progress')->where('table_name', $table)->where('direction', 'pull')->delete();
                    if ($oldProgress) {
                        DB::table('sync_table_progress')->insert((array) $oldProgress);
                    }
                }
            });
            throw $e;
        }
    }

    public function disconnectWithCentralOwner(string $login, string $password): array
    {
        $identity = $this->verifyCurrentOwner($login, $password);
        Settings::set('branch.owner_uuid', $identity['owner_uuid']);

        return $this->disconnect();
    }

    public function scheduleFactoryReset(
        string $login,
        string $password,
        PendingFactoryReset $pending,
    ): array {
        $this->ensureDesktopSqlite();
        $identity = $this->verifyCurrentOwner($login, $password);
        $backup = $this->backup('factory_reset', [
            'verified_branch_id' => $identity['branch_id'],
            'verified_owner_uuid' => $identity['owner_uuid'],
            'pending_push' => $this->pendingPushCounts(),
        ]);
        $database = (string) config('database.connections.sqlite.database');
        $marker = $pending->schedule($database, $backup['path']);

        return ['backup' => $backup, 'marker' => $marker];
    }

    public function disconnect(): array
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException('إعادة الإعداد الآمنة متاحة لقواعد SQLite فقط.');
        }

        $runtimeStatus = Schema::hasTable('sync_runtime_status')
            ? DB::table('sync_runtime_status')->where('id', 1)->value('status')
            : null;
        if ($runtimeStatus === 'syncing') {
            throw new RuntimeException('لا يمكن فصل الفرع أثناء مزامنة نشطة.');
        }

        $metadata = $this->identityMetadata('disconnect');
        if (empty($metadata['prior_branch_id']) || empty($metadata['prior_owner_uuid'])) {
            throw new RuntimeException('هوية الفرع السابقة غير مكتملة؛ لا يمكن تنفيذ فصل آمن.');
        }
        $metadata['cleared_sync_metadata'] = $this->syncMetadataSnapshot();
        $backup = $this->backups->create(null, $metadata);

        DB::transaction(function () use ($metadata, $backup): void {
            Settings::set('reconfigure.guard', '1');
            Settings::set('reconfigure.prior_branch_id', $metadata['prior_branch_id']);
            Settings::set('reconfigure.prior_owner_uuid', $metadata['prior_owner_uuid']);
            Settings::set('reconfigure.backup', $backup['path']);

            foreach ([
                'branch.id', 'branch.code', 'branch.name', 'branch.owner_uuid', 'sync.server_url', 'sync.token',
                'sync.enabled', 'registered_at', 'initial_sync_completed_at', 'initial_setup_completed_at',
            ] as $key) {
                Settings::forget($key);
            }

            DB::table('sync_state')->delete();
            if (Schema::hasTable('sync_table_progress')) {
                DB::table('sync_table_progress')
                    ->where('direction', 'pull')
                    ->where('sync_mode', 'initial')
                    ->delete();
            }
        });

        config([
            'sync.branch_id' => null,
            'sync.branch_code' => null,
            'sync.server_url' => null,
            'sync.token' => null,
            'sync.enabled' => false,
        ]);

        return $backup;
    }

    public function hasOperationalRows(): bool
    {
        $tables = array_values(array_unique(array_merge(
            config('sync.push', []),
            config('sync.bidirectional', []),
            ['sub_users']
        )));

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                return true;
            }
        }

        return false;
    }

    /** @return array{branch_id:string,owner_uuid:string} */
    private function verifyCurrentOwner(string $login, string $password): array
    {
        $this->ensureDesktopSqlite();
        $serverUrl = (string) Settings::get('sync.server_url', '');
        $token = (string) Settings::get('sync.token', '');
        $branchId = (string) Settings::get('branch.id', '');
        if ($serverUrl === '' || $token === '' || $branchId === '') {
            throw new RuntimeException('بيانات اتصال الفرع غير مكتملة؛ استخدم شاشة الإعداد.');
        }

        return $this->owners->verify($serverUrl, $token, $branchId, $login, $password);
    }

    private function ensureDesktopSqlite(): void
    {
        if (! \App\Support\Runtime::isDesktop() || DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException('إجراءات تعافي سطح المكتب متاحة داخل تطبيق Desktop فقط.');
        }
    }

    private function pendingPushCounts(): array
    {
        $counts = [];
        foreach (array_unique(array_merge(config('sync.push', []), config('sync.bidirectional', []))) as $table) {
            $model = config("sync.models.{$table}");
            if ($model && class_exists($model) && Schema::hasTable($table) && method_exists($model, 'scopePendingSync')) {
                $counts[$table] = $model::query()->pendingSync()->count();
            }
        }

        return $counts;
    }

    private function identityMetadata(string $reason): array
    {
        return [
            'reason' => $reason,
            'prior_branch_id' => Settings::get('branch.id'),
            'prior_branch_code' => Settings::get('branch.code'),
            'prior_branch_name' => Settings::get('branch.name'),
            'prior_owner_uuid' => Settings::get('branch.owner_uuid') ?: auth()->user()?->uuid,
        ];
    }

    private function syncMetadataSnapshot(?string $table = null): array
    {
        $stateQuery = DB::table('sync_state');
        if ($table !== null) {
            $stateQuery->where('table_name', $table);
        }

        $snapshot = [
            'sync_state' => $stateQuery->get()->map(fn ($row) => (array) $row)->all(),
            'sync_table_progress' => [],
        ];

        if (Schema::hasTable('sync_table_progress')) {
            $progressQuery = DB::table('sync_table_progress')->where('direction', 'pull');
            if ($table !== null) {
                $progressQuery->where('table_name', $table);
            }
            $snapshot['sync_table_progress'] = $progressQuery->get()->map(fn ($row) => (array) $row)->all();
        }

        return $snapshot;
    }
}
