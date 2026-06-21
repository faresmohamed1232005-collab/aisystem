<?php

namespace App\Listeners;

use App\Services\UpdateState;
use Native\Desktop\Events\AutoUpdater\UpdateAvailable;
use Native\Desktop\Events\AutoUpdater\UpdateDownloaded;

/**
 * يلتقط أحداث NativePHP AutoUpdater ويحدّث UpdateState ليعرضها الواجهة.
 *
 * UpdateAvailable  → الحالة "available" (نعرض بانر «يوجد تحديث» + زر «حدّث الآن»).
 * UpdateDownloaded → الحالة "downloaded" (نعرض «جاهز للتثبيت» + زر «أعد التشغيل وثبّت»).
 *
 * لا نثبّت تلقائياً — التثبيت بإذن المستخدم (يضغط الزر) عبر Sync/Update controller.
 */
class HandleUpdateEvents
{
    public function handleAvailable(UpdateAvailable $event): void
    {
        $notes = is_array($event->releaseNotes)
            ? implode("\n", array_filter(array_map(fn ($n) => is_array($n) ? ($n['note'] ?? '') : $n, $event->releaseNotes)))
            : $event->releaseNotes;

        UpdateState::setAvailable($event->version, $notes);
    }

    public function handleDownloaded(UpdateDownloaded $event): void
    {
        UpdateState::setDownloaded($event->version);
    }
}
