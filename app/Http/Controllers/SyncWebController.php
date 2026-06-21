<?php

namespace App\Http\Controllers;

use App\Services\Sync\SyncRunner;
use App\Services\Sync\SyncStatus;
use Illuminate\Http\JsonResponse;

/**
 * تشغيل المزامنة من الواجهة + عرض حالتها وسجلّها.
 *
 * run:    دورة كاملة (Push ثم Pull) عبر SyncRunner — تشغيل يدوي (يتجاهل backoff).
 * status: الحالة اللحظية + آخر السجلات (للـ polling ولوحة الحالة).
 */
class SyncWebController extends Controller
{
    public function run(SyncRunner $runner): JsonResponse
    {
        if (!config('sync.enabled')) {
            return response()->json(['success' => false, 'message' => 'المزامنة غير مُفعّلة.'], 400);
        }

        $result = $runner->run(manual: true);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'pushed'  => $result['pushed'] ?? 0,
            'pulled'  => $result['pulled'] ?? 0,
        ], $result['success'] ? 200 : 422);
    }

    /** الحالة اللحظية + آخر السجلات (لمؤشر الاتصال ولوحة الحالة). */
    public function status(): JsonResponse
    {
        return response()->json([
            'state' => SyncStatus::getState(),
            'logs'  => SyncStatus::recentLogs(20),
        ]);
    }
}
