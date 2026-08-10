<?php

namespace App\Services\Sync;

use App\Support\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncManifestService
{
    /**
     * Fetch and persist the initial-sync snapshot. A 404 means an older server and
     * creates resumable indeterminate progress without preventing synchronization.
     */
    public function initialize(array $tables): array
    {
        $existing = DB::table('sync_table_progress')
            ->where('direction', 'pull')
            ->where('sync_mode', 'initial')
            ->whereIn('table_name', $tables)
            ->exists();

        if ($existing) {
            $this->ensureRows($tables);
            return $this->progress($tables);
        }

        $serverUrl = rtrim((string) config('sync.server_url'), '/');
        $token = (string) config('sync.token');
        if ($serverUrl === '' || $token === '') {
            throw new \RuntimeException('إعدادات المزامنة ناقصة (server_url / token).');
        }

        $response = Http::withHeaders(['X-Sync-Token' => $token])
            ->timeout(120)
            ->acceptJson()
            ->post($serverUrl . '/api/sync/manifest', ['branch_id' => Branch::id()]);

        if ($response->status() === 404) {
            $this->persistFallback($tables);
            return $this->progress($tables);
        }
        if (! $response->successful() || ! $response->json('success')) {
            throw new \RuntimeException($response->json('message', 'تعذّر تحميل بيان المزامنة.'));
        }

        $manifestId = (string) ($response->json('manifest_id') ?: Str::ulid());
        $version = (int) $response->json('version', 1);
        $generatedAt = $response->json('generated_at');
        $manifestTables = $response->json('tables', []);
        $now = now();

        DB::transaction(function () use ($tables, $manifestTables, $manifestId, $version, $generatedAt, $now) {
            // A newly accepted manifest describes the complete bounded snapshot. Existing
            // normal-pull cursors would skip rows while progress starts from zero, so reset
            // them once here. Resuming an existing manifest returns above and never resets.
            DB::table('sync_state')->whereIn('table_name', $tables)->delete();

            foreach ($tables as $table) {
                $entry = $manifestTables[$table] ?? ['total' => 0, 'until' => null];
                $total = max(0, (int) ($entry['total'] ?? 0));
                DB::table('sync_table_progress')->updateOrInsert(
                    ['table_name' => $table, 'direction' => 'pull'],
                    [
                        'sync_mode' => 'initial',
                        'manifest_id' => $manifestId,
                        'manifest_version' => $version,
                        'manifest_generated_at' => $generatedAt,
                        'status' => $total === 0 ? 'completed' : 'pending',
                        'total_rows' => $total,
                        'processed_rows' => 0,
                        'succeeded_rows' => 0,
                        'failed_rows' => 0,
                        'last_error' => null,
                        'until_cursor' => isset($entry['until']) ? json_encode($entry['until']) : null,
                        'started_at' => null,
                        'last_attempt_at' => null,
                        'completed_at' => $total === 0 ? $now : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        });

        return $this->progress($tables);
    }

    public function progress(array $tables): array
    {
        return DB::table('sync_table_progress')
            ->where('direction', 'pull')
            ->whereIn('table_name', $tables)
            ->orderByRaw('CASE table_name ' . collect($tables)->map(fn ($table, $index) => "WHEN ? THEN {$index}")->implode(' ') . ' END', $tables)
            ->get()
            ->map(function ($row) {
                $data = (array) $row;
                if (! empty($data['last_error'])) {
                    $data['last_error'] = "تعذّر تنزيل بيانات {$data['table_name']}. يمكنك إعادة المحاولة.";
                }

                return $data;
            })
            ->all();
    }

    private function ensureRows(array $tables): void
    {
        $now = now();
        foreach ($tables as $table) {
            DB::table('sync_table_progress')->insertOrIgnore([
                'table_name' => $table,
                'direction' => 'pull',
                'sync_mode' => 'initial',
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function persistFallback(array $tables): void
    {
        $now = now();
        foreach ($tables as $table) {
            DB::table('sync_table_progress')->updateOrInsert(
                ['table_name' => $table, 'direction' => 'pull'],
                [
                    'sync_mode' => 'initial',
                    'manifest_id' => null,
                    'manifest_version' => null,
                    'manifest_generated_at' => null,
                    'status' => 'pending',
                    'total_rows' => 0,
                    'processed_rows' => 0,
                    'succeeded_rows' => 0,
                    'failed_rows' => 0,
                    'last_error' => null,
                    'until_cursor' => null,
                    'completed_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
