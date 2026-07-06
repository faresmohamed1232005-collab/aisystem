<?php

namespace App\Providers;

use App\Listeners\HandleUpdateEvents;
use App\Support\Settings;
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
        // عند التشغيل كتطبيق ديسكتوب (NativePHP)، حوّل قاعدة البيانات تلقائياً إلى SQLite.
        // NativePHP لا يحزّم MySQL، فاتصال mysql الموروث من بيئة الويب سيفشل دائماً.
        // الويب يبقى MySQL كما هو لأن المتغير غير مضبوط في بيئة المتصفح.
        if (env('NATIVEPHP_RUNNING') || config('nativephp-internal.running')) {
            $dbPath = storage_path('app/pharmacy-branch.sqlite');
            if (! file_exists($dbPath)) {
                @mkdir(dirname($dbPath), 0755, true);
                touch($dbPath);
            }
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => $dbPath,
                'database.connections.sqlite.foreign_key_constraints' => true,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // هيّئ إعدادات المزامنة من المخزن الدائم (شاشة الإعداد) قبل أي استخدام.
        $this->hydrateSyncConfigFromSettings();

        // ربط أحداث التحديث (NativePHP AutoUpdater) بمعالجها.
        // محميّ بـ class_exists حتى لا يفشل تشغيل السيرفر (بيئة الويب بدون NativePHP).
        if (class_exists(UpdateAvailable::class)) {
            Event::listen(UpdateAvailable::class, [HandleUpdateEvents::class, 'handleAvailable']);
            Event::listen(UpdateDownloaded::class, [HandleUpdateEvents::class, 'handleDownloaded']);
        }
    }

    /**
     * انسخ هوية الفرع/إعدادات المزامنة من Settings الدائم إلى config('sync.*').
     * هكذا تعمل كل قراءات config('sync.*') الحالية من المخزن الدائم دون تغيير مواضعها.
     * على السيرفر (لا Settings) تبقى قيم env كما هي — لا نطمس بقيمة فارغة.
     */
    private function hydrateSyncConfigFromSettings(): void
    {
        $map = [
            'branch.id'       => 'sync.branch_id',
            'branch.code'     => 'sync.branch_code',
            'sync.server_url' => 'sync.server_url',
            'sync.token'      => 'sync.token',
        ];

        foreach ($map as $settingKey => $configKey) {
            $value = Settings::get($settingKey);
            if (!empty($value)) {
                config([$configKey => $value]);
            }
        }

        // فعّل المزامنة تلقائياً بمجرد أن يصبح الفرع مسجّلاً (رابط + توكن موجودان).
        if (Settings::has('sync.server_url') && Settings::has('sync.token')) {
            config(['sync.enabled' => true]);
        }
    }
}
