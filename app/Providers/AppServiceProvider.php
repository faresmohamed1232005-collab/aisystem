<?php

namespace App\Providers;

use App\Listeners\HandleUpdateEvents;
use App\Services\Diagnostics\PendingFactoryReset;
use App\Support\Actor;
use App\Support\Roles;
use App\Support\Runtime;
use App\Support\Settings;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;
use Native\Desktop\Events\AutoUpdater\CheckingForUpdate;
use Native\Desktop\Events\AutoUpdater\DownloadProgress;
use Native\Desktop\Events\AutoUpdater\Error as AutoUpdaterError;
use Native\Desktop\Events\AutoUpdater\UpdateAvailable;
use Native\Desktop\Events\AutoUpdater\UpdateCancelled;
use Native\Desktop\Events\AutoUpdater\UpdateDownloaded;
use Native\Desktop\Events\AutoUpdater\UpdateNotAvailable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // NativePHP يمرّر مسار قاعدة الإنتاج الدائمة داخل userData. لا نفرض قاعدة أخرى
        // في storage لأن NativePHP يعيد كتابة الاتصال لاحقاً، فينتج مساران مختلفان.
        if (Runtime::isDesktop() && env('NATIVEPHP_DATABASE_PATH')) {
            $database = (string) env('NATIVEPHP_DATABASE_PATH');
            // طلب factory reset يُنفّذ قبل أول اتصال SQLite، حتى لا ننقل ملفاً مفتوحاً.
            $this->app->make(PendingFactoryReset::class)->process($database);
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => $database,
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

        // سجّل Gates الصلاحيات (RBAC). كل Gate يستشير Actor (المالك أو الموظف) لا
        // Auth::user() مباشرة، لأن Auth::id() دائماً المالك بينما الفاعل قد يكون موظفاً.
        $this->registerRoleGates();

        // تحذير خفيف للتخطيط: قراءات محلية فقط، بلا تقرير كامل أو اتصال بعيد.
        View::composer('layouts.app', function ($view): void {
            $warning = false;
            try {
                if (Schema::hasTable('sync_runtime_status')) {
                    $runtime = DB::table('sync_runtime_status')->where('id', 1)->first(['status', 'consecutive_failures']);
                    $warning = $runtime && ($runtime->status === 'offline' || (int) $runtime->consecutive_failures > 0);
                }
            } catch (Throwable) {
                $warning = true;
            }
            $view->with('diagnosticsWarning', $warning);
        });

        // ربط أحداث التحديث (NativePHP AutoUpdater) بمعالجها.
        // محميّ بـ class_exists حتى لا يفشل تشغيل السيرفر (بيئة الويب بدون NativePHP).
        if (class_exists(UpdateAvailable::class)) {
            Event::listen(CheckingForUpdate::class, [HandleUpdateEvents::class, 'handleChecking']);
            Event::listen(UpdateAvailable::class, [HandleUpdateEvents::class, 'handleAvailable']);
            Event::listen(UpdateNotAvailable::class, [HandleUpdateEvents::class, 'handleNotAvailable']);
            Event::listen(DownloadProgress::class, [HandleUpdateEvents::class, 'handleProgress']);
            Event::listen(UpdateDownloaded::class, [HandleUpdateEvents::class, 'handleDownloaded']);
            Event::listen(UpdateCancelled::class, [HandleUpdateEvents::class, 'handleCancelled']);
            Event::listen(AutoUpdaterError::class, [HandleUpdateEvents::class, 'handleError']);
        }
    }

    /**
     * سجّل كل صلاحية كـ Gate يستشير Actor. هكذا يعمل middleware('can:ability')
     * و@can('ability') مع نظام الأدوار المخصّص (المالك/الموظف) بلا تغيير في مواضعها.
     */
    private function registerRoleGates(): void
    {
        foreach (Roles::ABILITIES as $ability) {
            Gate::define($ability, fn ($user = null) => Actor::can($ability));
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
