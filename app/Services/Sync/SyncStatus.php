<?php

namespace App\Services\Sync;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * حالة المزامنة وسجلها وآلية الـ backoff، مع نسخة دائمة تتحمل مسح الكاش.
 */
class SyncStatus
{
    private const STATE_KEY = 'sync.status';
    private const BACKOFF_KEY = 'sync.backoff';
    private const NEXT_TRY_KEY = 'sync.next_try';
    private const RUNTIME_ID = 1;
    private const MAX_BACKOFF_SECONDS = 900;

    public static function record(string $direction, bool $success, int $rows, ?string $message, int $durationMs): void
    {
        DB::table('sync_logs')->insert([
            'direction' => $direction,
            'status' => $success ? 'success' : 'failed',
            'rows' => $rows,
            'message' => $message ? mb_substr($message, 0, 1000) : null,
            'duration_ms' => $durationMs,
            'created_at' => Carbon::now(),
        ]);

        $cutoff = DB::table('sync_logs')->orderByDesc('id')->skip(200)->take(1)->value('id');
        if ($cutoff) {
            DB::table('sync_logs')->where('id', '<=', $cutoff)->delete();
        }
    }

    public static function setState(string $status, ?string $message = null): void
    {
        $current = self::getState();
        $now = Carbon::now();
        $state = [
            'status' => $status,
            'message' => $message,
            'last_success' => $current['last_success'] ?? null,
            'last_attempt' => $now->toDateTimeString(),
        ];

        if ($status === 'online') {
            $state['last_success'] = $now->toDateTimeString();
        }

        Cache::put(self::STATE_KEY, $state, $now->copy()->addDay());
        self::updateRuntime([
            'status' => $status,
            'message' => $message,
            'last_success_at' => $state['last_success'],
            'last_attempt_at' => $state['last_attempt'],
        ]);
    }

    public static function getState(): array
    {
        $cached = Cache::get(self::STATE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $runtime = self::runtimeRow();
        $state = $runtime ? [
            'status' => $runtime->status,
            'message' => $runtime->message,
            'last_success' => self::dateString($runtime->last_success_at),
            'last_attempt' => self::dateString($runtime->last_attempt_at),
        ] : self::defaultState();

        Cache::put(self::STATE_KEY, $state, now()->addDay());

        return $state;
    }

    public static function canAttempt(): bool
    {
        $nextTry = Cache::get(self::NEXT_TRY_KEY);
        if ($nextTry === null) {
            $nextTry = self::runtimeRow()?->next_attempt_at;
            if ($nextTry !== null) {
                Cache::put(self::NEXT_TRY_KEY, self::dateString($nextTry), now()->addDay());
            }
        }

        return $nextTry === null || Carbon::now()->gte(Carbon::parse($nextTry));
    }

    public static function markSuccess(): void
    {
        Cache::forget(self::BACKOFF_KEY);
        Cache::forget(self::NEXT_TRY_KEY);
        self::updateRuntime([
            'consecutive_failures' => 0,
            'next_attempt_at' => null,
        ]);
    }

    public static function markFailure(): void
    {
        $durableFails = (int) (self::runtimeRow()?->consecutive_failures ?? 0);
        $fails = max((int) Cache::get(self::BACKOFF_KEY, 0), $durableFails) + 1;
        $nextTry = Carbon::now()->addSeconds(
            min(self::MAX_BACKOFF_SECONDS, (int) pow(2, min($fails, 10)))
        );

        Cache::put(self::BACKOFF_KEY, $fails, now()->addDay());
        Cache::put(self::NEXT_TRY_KEY, $nextTry->toDateTimeString(), now()->addDay());
        self::updateRuntime([
            'consecutive_failures' => $fails,
            'next_attempt_at' => $nextTry,
        ]);
    }

    public static function recentLogs(int $limit = 20): array
    {
        return DB::table('sync_logs')->orderByDesc('id')->limit($limit)->get()->toArray();
    }

    private static function defaultState(): array
    {
        return [
            'status' => 'idle',
            'message' => null,
            'last_success' => null,
            'last_attempt' => null,
        ];
    }

    private static function runtimeRow(): ?object
    {
        try {
            if (!Schema::hasTable('sync_runtime_status')) {
                return null;
            }

            return DB::table('sync_runtime_status')->where('id', self::RUNTIME_ID)->first();
        } catch (Throwable) {
            return null;
        }
    }

    private static function updateRuntime(array $values): void
    {
        try {
            if (!Schema::hasTable('sync_runtime_status')) {
                return;
            }

            DB::table('sync_runtime_status')->updateOrInsert(
                ['id' => self::RUNTIME_ID],
                array_merge($values, ['updated_at' => Carbon::now()])
            );
        } catch (Throwable) {
            // التطبيق يجب أن يظل قابلاً للإقلاع قبل تشغيل migrations أو أثناء تعافي DB.
        }
    }

    private static function dateString(mixed $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toDateTimeString();
    }
}
