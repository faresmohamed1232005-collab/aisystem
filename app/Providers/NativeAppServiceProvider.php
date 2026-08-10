<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Native\Desktop\Facades\AutoUpdater;
use Native\Desktop\Facades\ChildProcess;
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

        // شغّل Laravel scheduler كعملية خلفية دائمة داخل التطبيق؛ فهو المسؤول عن
        // المزامنة الدورية والنسخة الاحتياطية اليومية حتى لو لم تبقَ صفحة مفتوحة.
        $this->startScheduler();

        // فحص وجود تحديث عند الإقلاع (لا يثبّت تلقائياً — يُعلِم المستخدم فقط).
        // أحداث AutoUpdater تحفظ الحالة لتعرضها الواجهة ومركز التشخيص.
        if (config('nativephp.updater.enabled')) {
            try {
                AutoUpdater::checkForUpdates();
            } catch (\Throwable $e) {
                // لا نوقف الإقلاع لو تعذّر الفحص (مثلاً لا يوجد نت).
                report($e);
            }
        }
    }

    private function startScheduler(): void
    {
        try {
            ChildProcess::artisan(
                ['schedule:work', '--quiet'],
                'laravel_scheduler',
                persistent: true,
            );
        } catch (\Throwable $e) {
            // لا نمنع الإقلاع؛ مركز التشخيص سيُظهر غياب نجاح المزامنة/النسخ لاحقاً.
            report($e);
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
