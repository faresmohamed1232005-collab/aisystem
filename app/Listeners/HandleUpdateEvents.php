<?php

namespace App\Listeners;

use App\Services\UpdateState;
use Native\Desktop\Events\AutoUpdater\CheckingForUpdate;
use Native\Desktop\Events\AutoUpdater\DownloadProgress;
use Native\Desktop\Events\AutoUpdater\Error as AutoUpdaterError;
use Native\Desktop\Events\AutoUpdater\UpdateAvailable;
use Native\Desktop\Events\AutoUpdater\UpdateCancelled;
use Native\Desktop\Events\AutoUpdater\UpdateDownloaded;
use Native\Desktop\Events\AutoUpdater\UpdateNotAvailable;

/** يلتقط أحداث NativePHP AutoUpdater ويحدّث UpdateState ليعرضها المستهلكون. */
class HandleUpdateEvents
{
    public function handleChecking(CheckingForUpdate $event): void
    {
        UpdateState::setChecking();
    }

    public function handleAvailable(UpdateAvailable $event): void
    {
        UpdateState::setAvailable($event->version, $this->notes($event->releaseNotes));
    }

    public function handleNotAvailable(UpdateNotAvailable $event): void
    {
        UpdateState::clear();
    }

    public function handleProgress(DownloadProgress $event): void
    {
        UpdateState::setDownloadProgress(
            $event->total,
            $event->delta,
            $event->transferred,
            $event->percent,
            $event->bytesPerSecond,
        );
    }

    public function handleDownloaded(UpdateDownloaded $event): void
    {
        UpdateState::setDownloaded($event->version);
    }

    public function handleCancelled(UpdateCancelled $event): void
    {
        UpdateState::setCancelled($event->version);
    }

    public function handleError(AutoUpdaterError $event): void
    {
        UpdateState::setFailed(trim($event->name . ': ' . $event->message));
    }

    private function notes(string|array|null $releaseNotes): ?string
    {
        if (!is_array($releaseNotes)) {
            return $releaseNotes;
        }

        return implode("\n", array_filter(array_map(
            fn ($note) => is_array($note) ? ($note['note'] ?? '') : $note,
            $releaseNotes
        )));
    }
}
