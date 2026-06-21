<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * UpdateState — حالة تحديث التطبيق المشتركة بين أحداث NativePHP والواجهة.
 *
 * أحداث AutoUpdater (UpdateAvailable / UpdateDownloaded) تُحدّث الحالة هنا،
 * والواجهة تستعلم عنها دورياً (polling) لعرض بانر «يوجد تحديث» وزر «حدّث الآن».
 *
 * نخزّن في الكاش (ملف/قاعدة) لأنها تُكتب من عملية الخلفية (Electron events) وتُقرأ
 * من طلبات الويب — فتحتاج مخزناً مشتركاً لا متغيّر في الذاكرة.
 */
class UpdateState
{
    private const KEY = 'app.update_state';

    /** الحالات: none | available | downloading | downloaded */
    public static function get(): array
    {
        return Cache::get(self::KEY, [
            'status'  => 'none',
            'version' => null,
            'notes'   => null,
        ]);
    }

    public static function setAvailable(string $version, ?string $notes = null): void
    {
        Cache::forever(self::KEY, [
            'status'  => 'available',
            'version' => $version,
            'notes'   => $notes,
        ]);
    }

    public static function setDownloading(): void
    {
        $state = self::get();
        $state['status'] = 'downloading';
        Cache::forever(self::KEY, $state);
    }

    public static function setDownloaded(string $version): void
    {
        Cache::forever(self::KEY, [
            'status'  => 'downloaded',
            'version' => $version,
            'notes'   => self::get()['notes'] ?? null,
        ]);
    }

    public static function clear(): void
    {
        Cache::forget(self::KEY);
    }
}
