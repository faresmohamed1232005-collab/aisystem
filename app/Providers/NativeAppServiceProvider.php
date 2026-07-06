<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
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
        // عند أول إقلاع على جهاز فرع، SQLite يكون فاضي — شغّل المهاجرات.
        $this->ensureDatabaseReady();

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

    /**
     * تأكد إن قاعدة SQLite بها كل الجداول. لو فاضية، شغّل migrate.
     */
    private function ensureDatabaseReady(): void
    {
        try {
            if (! Schema::hasTable('users')) {
                Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
            }
        } catch (\Throwable $e) {
            // لو فشل (مثلاً لقفل أو خطأ مؤقت)، حاول migrate بأي حال — أسوأ سيناريو لن يضيف شيئاً.
            try {
                Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
            } catch (\Throwable $e2) {
                report($e2);
            }
        }
    }
}
