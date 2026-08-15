<?php

namespace App\Services\Sync;

use App\Support\Branch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SyncPullService — جانب الفرع: يسحب تحديثات الكتالوج/المراجع من السيرفر (master).
 *
 * الخطوات:
 *   1. اقرأ cursor كل جدول قابل للسحب من sync_state (آخر updated_at استُلم).
 *   2. اطلب من /api/sync/pull الصفوف المتغيّرة بعد الـ cursor.
 *   3. طبّقها محلياً عبر SyncRepository (يترجم <fk>_uuid → id محلي ويعمل upsert بالـ uuid).
 *   4. حدّث cursor الجدول، وكرّر إن كان هناك المزيد (more=true).
 *
 * القاعدة: السيرفر master للكتالوج → last-write-wins (الصف القادم يحدّث المحلي).
 */
class SyncPullService
{
    /** أقصى عدد دورات لكل جدول في التشغيل الكامل (حماية من حلقة لا نهائية). */
    private const MAX_ROUNDS = 1000;

    public function __construct(private SyncRepository $repo) {}

    /**
     * @return array{success:bool, pulled:int, message:string, detail?:array}
     */
    public function run(): array
    {
        $serverUrl = rtrim((string) config('sync.server_url'), '/');
        $token     = (string) config('sync.token');

        if ($serverUrl === '' || $token === '') {
            return ['success' => false, 'pulled' => 0, 'message' => 'إعدادات المزامنة ناقصة (server_url / token).'];
        }

        // ما يسحبه الفرع = pull (الكتالوج/الماستر) + pull_scoped (التحويلات/اللقطات).
        $pullable    = array_merge(config('sync.pull', []), array_keys(config('sync.pull_scoped', [])));
        $totalPulled = 0;
        $detail      = [];

        foreach ($pullable as $table) {
            try {
                $pulled = $this->pullTable($serverUrl, $token, $table);
                $this->recordNormalProgress($table, $pulled, null);
                $totalPulled += $pulled;
                $detail[$table] = $pulled;
            } catch (\Throwable $e) {
                $this->recordNormalProgress($table, 0, $e->getMessage());
                Log::warning("Sync pull failed for {$table}", ['error' => $e->getMessage()]);
                return ['success' => false, 'pulled' => $totalPulled, 'message' => "تعذّر سحب {$table}: " . $e->getMessage(), 'detail' => $detail];
            }
        }

        return [
            'success' => true,
            'pulled'  => $totalPulled,
            'message' => $totalPulled > 0 ? "تم تنزيل {$totalPulled} تحديث من السيرفر." : 'لا تحديثات جديدة من السيرفر.',
            'detail'  => $detail,
        ];
    }

    /**
     * اسحب دفعة واحدة من جدول. تُستخدم في شاشة أول مزامنة حتى يبقى كل HTTP request قصيراً.
     *
     * @return array{table:string,pulled:int,total:int,more:bool}
     */
    public function pullStep(string $table): array
    {
        if (! in_array($table, $this->pullableTables(), true)) {
            throw new \InvalidArgumentException("جدول غير قابل للسحب: {$table}");
        }

        $serverUrl = rtrim((string) config('sync.server_url'), '/');
        $token = (string) config('sync.token');
        if ($serverUrl === '' || $token === '') {
            throw new \RuntimeException('إعدادات المزامنة ناقصة (server_url / token).');
        }

        $progress = DB::table('sync_table_progress')
            ->where('table_name', $table)->where('direction', 'pull')->first();
        if (! $progress) {
            DB::table('sync_table_progress')->insert([
                'table_name' => $table,
                'direction' => 'pull',
                'sync_mode' => 'initial',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $progress = DB::table('sync_table_progress')
                ->where('table_name', $table)->where('direction', 'pull')->first();
        }
        $until = $progress?->until_cursor ? json_decode($progress->until_cursor, true) : null;
        $now = Carbon::now();

        DB::table('sync_table_progress')->where('table_name', $table)->where('direction', 'pull')->update([
            'status' => 'running',
            'started_at' => $progress?->started_at ?: $now,
            'last_attempt_at' => $now,
            'last_error' => null,
            'updated_at' => $now,
        ]);

        try {
            $result = $this->pullBatch($serverUrl, $token, $table, $until);
            $pulled = $result['pulled'];
            $succeeded = min((int) ($progress?->total_rows ?: PHP_INT_MAX), (int) ($progress?->succeeded_rows ?? 0) + $pulled);
            if (! $progress?->manifest_id) {
                $succeeded = (int) ($progress?->succeeded_rows ?? 0) + $pulled;
            }
            $completed = ! $result['more'];
            if ($completed && $progress?->manifest_id && $succeeded < (int) $progress->total_rows) {
                throw new \RuntimeException("انتهى الخادم قبل تنزيل إجمالي {$table} المحدد في البيان.");
            }

            DB::table('sync_table_progress')->where('table_name', $table)->where('direction', 'pull')->update([
                'status' => $completed ? 'completed' : 'running',
                'processed_rows' => $succeeded,
                'succeeded_rows' => $succeeded,
                'last_error' => null,
                'cursor' => isset($result['cursor']) ? json_encode($result['cursor']) : null,
                'completed_at' => $completed ? $now : null,
                'updated_at' => $now,
            ]);

            return [...$result, 'progress' => $this->progressRow($table)];
        } catch (\Throwable $e) {
            DB::table('sync_table_progress')->where('table_name', $table)->where('direction', 'pull')->update([
                'status' => 'failed',
                'failed_rows' => DB::raw('failed_rows + 1'),
                'last_error' => "تعذّر تنزيل بيانات {$table}. يمكنك إعادة المحاولة.",
                'last_attempt_at' => $now,
                'updated_at' => $now,
            ]);
            throw $e;
        }
    }

    /** @return array<int,string> */
    public function pullableTables(): array
    {
        return array_values(array_unique(array_merge(
            config('sync.pull', []),
            array_keys(config('sync.pull_scoped', []))
        )));
    }

    /**
     * اسحب جدولاً واحداً على دفعات حتى يفرغ. يرجّع عدد الصفوف المطبّقة.
     */
    private function pullTable(string $serverUrl, string $token, string $table): int
    {
        $applied = 0;

        for ($round = 0; $round < self::MAX_ROUNDS; $round++) {
            $result = $this->pullBatch($serverUrl, $token, $table);
            $applied += $result['pulled'];

            if (! $result['more']) {
                return $applied;
            }
        }

        throw new \RuntimeException("توقّف سحب {$table} قبل اكتماله بعد " . self::MAX_ROUNDS . ' دفعة.');
    }

    /** @return array{table:string,pulled:int,more:bool,cursor:mixed} */
    private function pullBatch(string $serverUrl, string $token, string $table, ?array $until = null): array
    {
        // cursor مركّب (keyset): {ts, uuid} — يمنع تخطّي صفوف بنفس الثانية.
        $state = DB::table('sync_state')->where('table_name', $table)->first();
        $cursor = $state && $state->last_pulled_at !== null
            ? ['ts' => $state->last_pulled_at, 'uuid' => $state->last_pulled_uuid]
            : null;

        // تعافٍ من نسخ Desktop القديمة التي تقدّم فيها cursor الحساب بينما طُبّق الصف
        // على مسار SQLite آخر أو رُفض بسبب تعارض id. الحساب صف واحد، فإعادة سحبه آمنة.
        if ($table === 'users' && ! empty($state?->last_pulled_uuid)
            && ! DB::table('users')->where('uuid', $state->last_pulled_uuid)->exists()) {
            $cursor = null;
        }

        $response = Http::withHeaders(['X-Sync-Token' => $token])
            ->timeout(120)
            ->acceptJson()
            ->post($serverUrl . '/api/sync/pull', [
                'cursors' => [$table => $cursor],
                'until' => $until ? [$table => $until] : [],
                'branch_id' => Branch::id(),
            ]);

        if (! $response->successful() || ! $response->json('success')) {
            throw new \RuntimeException($response->json('message', 'استجابة غير متوقعة من السيرفر'));
        }

        $payload = $response->json("tables.{$table}", ['rows' => [], 'cursor' => $cursor, 'more' => false]);
        $rows = $payload['rows'] ?? [];

        if (! empty($rows)) {
            DB::transaction(function () use ($table, $rows) {
                $result = $this->repo->applyBatch($table, $rows, true);
                if (($result['rejected'] ?? 0) > 0) {
                    throw new \RuntimeException(implode(' | ', array_slice($result['errors'] ?? [], 0, 3)));
                }
            });
        }

        $newCursor = $payload['cursor'] ?? $cursor;
        if (is_array($newCursor) && ! empty($newCursor['ts'])) {
            DB::table('sync_state')->updateOrInsert(
                ['table_name' => $table],
                [
                    'last_pulled_at' => $newCursor['ts'],
                    'last_pulled_uuid' => $newCursor['uuid'] ?? null,
                    'last_pull_run_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }

        return [
            'table' => $table,
            'pulled' => count($rows),
            'more' => ! empty($payload['more']),
            'cursor' => $newCursor,
        ];
    }

    private function progressRow(string $table): array
    {
        return (array) DB::table('sync_table_progress')
            ->where('table_name', $table)
            ->where('direction', 'pull')
            ->first();
    }

    private function recordNormalProgress(string $table, int $pulled, ?string $error): void
    {
        $existing = DB::table('sync_table_progress')
            ->where('table_name', $table)->where('direction', 'pull')->first();

        // نحافظ على sync_mode='initial' و total_rows كلقطة أولى (سياق «كم صف نزّلنا مبدئياً»)،
        // لكن لا نُجمّد بقية الأرقام: كل سحب لاحق يُحدّث الحالة وآخر محاولة وآخر خطأ، ويزيد
        // العدّادات بما جرى فعلياً — حتى يرى المستخدم في مركز التشخيص أن المزامنة تعمل وتتقدّم.
        if ($existing && $existing->sync_mode === 'initial') {
            DB::table('sync_table_progress')->where('id', $existing->id)->update([
                'status' => $error ? 'failed' : 'completed',
                'succeeded_rows' => (int) $existing->succeeded_rows + ($error ? 0 : $pulled),
                'processed_rows' => (int) $existing->processed_rows + ($error ? 0 : $pulled),
                'failed_rows' => (int) $existing->failed_rows + ($error ? 1 : 0),
                'last_attempt_at' => now(),
                'last_error' => $error,
                'completed_at' => $error ? $existing->completed_at : now(),
                'updated_at' => now(),
            ]);
            return;
        }

        DB::table('sync_table_progress')->updateOrInsert(
            ['table_name' => $table, 'direction' => 'pull'],
            [
                'sync_mode' => 'normal',
                'status' => $error ? 'failed' : 'completed',
                'total_rows' => $pulled,
                'processed_rows' => $pulled,
                'succeeded_rows' => $pulled,
                'failed_rows' => $error ? 1 : 0,
                'last_error' => $error,
                'last_attempt_at' => now(),
                'completed_at' => $error ? null : now(),
                'created_at' => $existing?->created_at ?? now(),
                'updated_at' => now(),
            ]
        );
    }
}
