<?php

namespace App\Services\Sync;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * SyncStatus — حالة المزامنة وسجلّها وآلية الـ backoff.
 *
 * المسؤوليات:
 *   - تسجيل كل دورة (push/pull) في جدول sync_logs.
 *   - حفظ حالة لحظية في الكاش (متصل/يتزامن/آخر نجاح) لتعرضها الواجهة.
 *   - إدارة backoff: بعد فشل متكرر نؤجّل المحاولة التالية تصاعدياً (لا نُغرق السيرفر).
 *
 * ملاحظة سلامة: لا نفقد أي تغيير عند الفشل — synced_at لا يُحدَّث إلا عند نجاح الرفع،
 * فالصفوف تبقى pendingSync وتُعاد محاولتها في الدورة التالية. الـ backoff يضبط التوقيت فقط.
 */
class SyncStatus
{
    private const STATE_KEY    = 'sync.status';   // الحالة اللحظية للعرض
    private const BACKOFF_KEY  = 'sync.backoff';  // عدد مرّات الفشل المتتالية
    private const NEXT_TRY_KEY = 'sync.next_try'; // أقرب وقت مسموح للمحاولة التالية

    private const MAX_BACKOFF_SECONDS = 900;      // سقف التأجيل: 15 دقيقة

    /** سجّل نتيجة دورة مزامنة في sync_logs والحالة اللحظية. */
    public static function record(string $direction, bool $success, int $rows, ?string $message, int $durationMs): void
    {
        DB::table('sync_logs')->insert([
            'direction'   => $direction,
            'status'      => $success ? 'success' : 'failed',
            'rows'        => $rows,
            'message'     => $message ? mb_substr($message, 0, 1000) : null,
            'duration_ms' => $durationMs,
            'created_at'  => Carbon::now(),
        ]);

        // تشذيب: نحتفظ بآخر 200 سجل فقط.
        $cutoff = DB::table('sync_logs')->orderByDesc('id')->skip(200)->take(1)->value('id');
        if ($cutoff) {
            DB::table('sync_logs')->where('id', '<=', $cutoff)->delete();
        }
    }

    /** حدّث الحالة اللحظية المعروضة في الواجهة. */
    public static function setState(string $status, ?string $message = null): void
    {
        $current = self::getState();
        $state = [
            'status'         => $status, // idle | syncing | online | offline
            'message'        => $message,
            'last_success'   => $current['last_success'] ?? null,
            'last_attempt'   => Carbon::now()->toDateTimeString(),
        ];
        if ($status === 'online') {
            $state['last_success'] = Carbon::now()->toDateTimeString();
        }
        Cache::put(self::STATE_KEY, $state, now()->addDay());
    }

    public static function getState(): array
    {
        return Cache::get(self::STATE_KEY, [
            'status'       => 'idle',
            'message'      => null,
            'last_success' => null,
            'last_attempt' => null,
        ]);
    }

    /** هل يُسمح بمحاولة مزامنة الآن؟ (احترام الـ backoff). */
    public static function canAttempt(): bool
    {
        $nextTry = Cache::get(self::NEXT_TRY_KEY);
        return $nextTry === null || Carbon::now()->gte(Carbon::parse($nextTry));
    }

    /** سجّل نجاحاً: صفّر الـ backoff. */
    public static function markSuccess(): void
    {
        Cache::forget(self::BACKOFF_KEY);
        Cache::forget(self::NEXT_TRY_KEY);
    }

    /** سجّل فشلاً: ضاعِف التأجيل تصاعدياً (exponential backoff بسقف). */
    public static function markFailure(): void
    {
        $fails = (int) Cache::get(self::BACKOFF_KEY, 0) + 1;
        Cache::put(self::BACKOFF_KEY, $fails, now()->addDay());

        // 2^fails ثانية بسقف MAX (1→2s, 2→4s, 3→8s ... حتى 15 دقيقة).
        $delay = min(self::MAX_BACKOFF_SECONDS, (int) pow(2, min($fails, 10)));
        Cache::put(self::NEXT_TRY_KEY, Carbon::now()->addSeconds($delay)->toDateTimeString(), now()->addDay());
    }

    /** آخر سجلات المزامنة (للعرض). */
    public static function recentLogs(int $limit = 20): array
    {
        return DB::table('sync_logs')->orderByDesc('id')->limit($limit)->get()->toArray();
    }
}
