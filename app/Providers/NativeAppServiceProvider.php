<?php

namespace App\Providers;

use Native\Desktop\Facades\AutoUpdater;
use Native\Desktop\Facades\Window;
use Native\Desktop\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open()
            ->title('نظام إدارة الصيدلية')
            ->width(1280)
            ->height(800)
            ->minWidth(1024)
            ->minHeight(700)
            ->maximized();

        // فحص وجود تحديث عند الإقلاع (لا يثبّت تلقائياً — يُعلِم المستخدم فقط).
        // أحداث AutoUpdater (UpdateAvailable/Downloaded) يلتقطها UpdateListener
        // ويخزّن الحالة في الكاش لتعرضها الواجهة كبانر "يوجد تحديث".
        if (config('nativephp.updater.enabled')) {
            try {
                AutoUpdater::checkForUpdates();
            } catch (\Throwable $e) {
                // لا نوقف الإقلاع لو تعذّر الفحص (مثلاً لا يوجد نت).
                report($e);
            }
        }
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
