<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\Log;

/**
 * SyncRunner — منسّق دورة المزامنة الكاملة مع طبقة الصلابة (P4).
 *
 * يلفّ Push ثم Pull، ويضيف:
 *   - احترام الـ backoff (لا يحاول قبل الوقت المسموح بعد فشل متكرر) — إلا في التشغيل اليدوي.
 *   - تسجيل كل اتجاه في sync_logs (نجاح/فشل، عدد الصفوف، المدة).
 *   - تحديث الحالة اللحظية (syncing/online/offline) لعرضها في الواجهة.
 *
 * يستخدمه: زر المزامنة اليدوي (SyncWebController) والأمر المجدوَل (sync:run).
 */
class SyncRunner
{
    public function __construct(
        private SyncPushService $push,
        private SyncPullService $pull,
    ) {}

    /**
     * @param  bool  $manual  تشغيل يدوي (يتجاهل الـ backoff — المستخدم طلبها صراحةً).
     * @return array{success:bool, pushed:int, pulled:int, message:string, skipped?:bool}
     */
    public function run(bool $manual = false): array
    {
        if (!config('sync.enabled')) {
            return ['success' => false, 'pushed' => 0, 'pulled' => 0, 'message' => 'المزامنة غير مُفعّلة.'];
        }

        // احترام الـ backoff في التشغيل التلقائي فقط.
        if (!$manual && !SyncStatus::canAttempt()) {
            return ['success' => true, 'pushed' => 0, 'pulled' => 0, 'message' => 'مؤجّلة (backoff).', 'skipped' => true];
        }

        SyncStatus::setState('syncing');

        $pushed = 0;
        $pulled = 0;
        $allOk  = true;
        $lastMessage = '';

        // ---- Push ----
        $t0 = microtime(true);
        try {
            $r = $this->push->run();
            $pushed = $r['pushed'] ?? 0;
            $allOk = $allOk && $r['success'];
            $lastMessage = $r['message'] ?? '';
            SyncStatus::record('push', $r['success'], $pushed, $r['message'] ?? null, (int) ((microtime(true) - $t0) * 1000));
        } catch (\Throwable $e) {
            $allOk = false;
            $lastMessage = $e->getMessage();
            SyncStatus::record('push', false, 0, $e->getMessage(), (int) ((microtime(true) - $t0) * 1000));
            Log::warning('Sync push threw', ['error' => $e->getMessage()]);
        }

        // ---- Pull ----
        $t1 = microtime(true);
        try {
            $r = $this->pull->run();
            $pulled = $r['pulled'] ?? 0;
            $allOk = $allOk && $r['success'];
            if (!($r['success'] ?? false)) {
                $lastMessage = $r['message'] ?? $lastMessage;
            }
            SyncStatus::record('pull', $r['success'], $pulled, $r['message'] ?? null, (int) ((microtime(true) - $t1) * 1000));
        } catch (\Throwable $e) {
            $allOk = false;
            $lastMessage = $e->getMessage();
            SyncStatus::record('pull', false, 0, $e->getMessage(), (int) ((microtime(true) - $t1) * 1000));
            Log::warning('Sync pull threw', ['error' => $e->getMessage()]);
        }

        // ---- حالة + backoff ----
        if ($allOk) {
            SyncStatus::markSuccess();
            SyncStatus::setState('online');
            $message = "تمت المزامنة: رُفع {$pushed} وسُحب {$pulled}.";
        } else {
            SyncStatus::markFailure();
            SyncStatus::setState('offline', $lastMessage);
            $message = $lastMessage ?: 'تعذّرت المزامنة.';
        }

        return ['success' => $allOk, 'pushed' => $pushed, 'pulled' => $pulled, 'message' => $message];
    }
}
