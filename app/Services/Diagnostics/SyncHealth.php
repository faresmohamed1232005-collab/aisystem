<?php

namespace App\Services\Diagnostics;

use App\Services\Sync\SyncStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncHealth
{
    public function report(): array
    {
        $state = SyncStatus::getState();
        $freshnessSeconds = $this->age($state['last_attempt'] ?? null);
        $failureCount = $this->failureCount();
        $progress = $this->tableProgress();
        $pending = $this->pendingPushCounts();
        $status = 'healthy';

        if (($state['status'] ?? 'idle') === 'offline' || $failureCount > 0 || $this->hasProgressFailures($progress)) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'enabled' => (bool) config('sync.enabled', false),
            'runtime' => $state,
            'freshness_seconds' => $freshnessSeconds,
            'consecutive_failures' => $failureCount,
            'table_progress' => $progress,
            'pending_push' => [
                'total' => array_sum($pending),
                'tables' => $pending,
            ],
        ];
    }

    private function failureCount(): int
    {
        try {
            if (!Schema::hasTable('sync_runtime_status')) {
                return 0;
            }

            return (int) DB::table('sync_runtime_status')->where('id', 1)->value('consecutive_failures');
        } catch (Throwable) {
            return 0;
        }
    }

    private function tableProgress(): array
    {
        try {
            if (!Schema::hasTable('sync_table_progress')) {
                return [];
            }

            return DB::table('sync_table_progress')
                ->orderBy('table_name')
                ->orderBy('direction')
                ->get([
                    'table_name', 'direction', 'sync_mode', 'status', 'total_rows', 'processed_rows',
                    'succeeded_rows', 'failed_rows', 'last_error', 'cursor', 'started_at',
                    'completed_at', 'updated_at',
                ])
                ->map(fn ($row) => (array) $row)
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function pendingPushCounts(): array
    {
        $tables = array_values(array_unique(array_merge(
            config('sync.push', []),
            config('sync.bidirectional', [])
        )));
        $models = config('sync.models', []);
        $counts = [];

        foreach ($tables as $table) {
            $model = $models[$table] ?? null;
            if (!$model || !class_exists($model) || !method_exists($model, 'scopePendingSync')) {
                continue;
            }

            try {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                $query = $model::query();
                if (method_exists($model, 'bootSoftDeletes')) {
                    $query->withTrashed();
                }
                $counts[$table] = $query->pendingSync()->count();
            } catch (Throwable) {
                // موديل/جدول جزئي أثناء migration لا يجب أن يُسقط تقرير التشخيص كله.
            }
        }

        return $counts;
    }

    private function age(?string $date): ?int
    {
        if ($date === null) {
            return null;
        }

        try {
            return abs(Carbon::parse($date)->diffInSeconds(now()));
        } catch (Throwable) {
            return null;
        }
    }

    private function hasProgressFailures(array $progress): bool
    {
        foreach ($progress as $row) {
            if (($row['status'] ?? null) === 'failed' || (int) ($row['failed_rows'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }
}
