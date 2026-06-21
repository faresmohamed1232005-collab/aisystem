<?php

namespace App\Providers;

use App\Listeners\HandleUpdateEvents;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Native\Desktop\Events\AutoUpdater\UpdateAvailable;
use Native\Desktop\Events\AutoUpdater\UpdateDownloaded;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ربط أحداث التحديث (NativePHP AutoUpdater) بمعالجها.
        // محميّ بـ class_exists حتى لا يفشل تشغيل السيرفر (بيئة الويب بدون NativePHP).
        if (class_exists(UpdateAvailable::class)) {
            Event::listen(UpdateAvailable::class, [HandleUpdateEvents::class, 'handleAvailable']);
            Event::listen(UpdateDownloaded::class, [HandleUpdateEvents::class, 'handleDownloaded']);
        }
    }
}
