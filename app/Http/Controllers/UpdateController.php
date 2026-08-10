<?php

namespace App\Http\Controllers;

use App\Services\UpdateState;
use App\Support\Actor;
use App\Support\Runtime;
use Illuminate\Http\JsonResponse;

/**
 * تحكّم تحديث التطبيق من الواجهة (بإذن المستخدم).
 *
 * status:   الواجهة تستعلم دورياً لعرض بانر «يوجد تحديث» / «جاهز للتثبيت».
 * download: المستخدم ضغط «حدّث الآن» → نبدأ تنزيل التحديث في الخلفية.
 * install:  المستخدم ضغط «أعد التشغيل وثبّت» بعد اكتمال التنزيل → quitAndInstall.
 */
class UpdateController extends Controller
{
    /** الحالة الحالية للتحديث (للـ polling من الواجهة). */
    public function status(): JsonResponse
    {
        return response()->json(UpdateState::get());
    }

    /** اطلب فحصاً يدوياً لقناة الإصدارات. */
    public function check(): JsonResponse
    {
        $this->authorizeDesktopOwner();

        if (! config('nativephp.updater.enabled')) {
            return response()->json(['success' => false, 'message' => 'التحديثات غير مُفعّلة في هذا الإصدار.'], 400);
        }

        \Native\Desktop\Facades\AutoUpdater::checkForUpdates();
        UpdateState::setChecking();

        return response()->json(['success' => true, 'message' => 'بدأ فحص التحديثات...']);
    }

    /** بدء تنزيل التحديث المتاح (لا يثبّت — ينتظر إذن التثبيت بعد التنزيل). */
    public function download(): JsonResponse
    {
        $this->authorizeDesktopOwner();
        $state = UpdateState::get();
        if ($state['status'] !== 'available') {
            return response()->json(['success' => false, 'message' => 'لا يوجد تحديث متاح للتنزيل.'], 400);
        }

        if (!class_exists(\Native\Desktop\Facades\AutoUpdater::class)) {
            return response()->json(['success' => false, 'message' => 'التحديث متاح فقط في تطبيق سطح المكتب.'], 400);
        }

        \Native\Desktop\Facades\AutoUpdater::downloadUpdate();
        UpdateState::setDownloading();

        return response()->json(['success' => true, 'message' => 'بدأ تنزيل التحديث...']);
    }

    /** تثبيت التحديث الذي اكتمل تنزيله (يعيد تشغيل التطبيق). */
    public function install(): JsonResponse
    {
        $this->authorizeDesktopOwner();
        $state = UpdateState::get();
        if ($state['status'] !== 'downloaded') {
            return response()->json(['success' => false, 'message' => 'التحديث لم يكتمل تنزيله بعد.'], 400);
        }

        if (!class_exists(\Native\Desktop\Facades\AutoUpdater::class)) {
            return response()->json(['success' => false, 'message' => 'التثبيت متاح فقط في تطبيق سطح المكتب.'], 400);
        }

        // يغلق التطبيق ويثبّت التحديث ثم يعيد التشغيل.
        \Native\Desktop\Facades\AutoUpdater::quitAndInstall();

        return response()->json(['success' => true, 'message' => 'جارٍ التثبيت وإعادة التشغيل...']);
    }

    private function authorizeDesktopOwner(): void
    {
        abort_unless(Runtime::isDesktop(), 404);
        abort_unless(Actor::isOwner(), 403);
    }
}
