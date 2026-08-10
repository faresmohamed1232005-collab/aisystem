<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/** حالة تحديث التطبيق المشتركة بين أحداث NativePHP والواجهة. */
class UpdateState
{
    private const KEY = 'app.update_state';

    /** الحالات: none | checking | available | downloading | downloaded | failed | cancelled */
    public static function get(): array
    {
        return array_merge(self::defaults(), Cache::get(self::KEY, []));
    }

    public static function setChecking(): void
    {
        self::put(['status' => 'checking', 'error' => null]);
    }

    public static function setAvailable(string $version, ?string $notes = null): void
    {
        self::put([
            'status' => 'available',
            'version' => $version,
            'notes' => $notes,
            'error' => null,
            'progress' => self::emptyProgress(),
        ]);
    }

    public static function setDownloading(): void
    {
        self::put(['status' => 'downloading', 'error' => null]);
    }

    public static function setDownloadProgress(
        int $total,
        int $delta,
        int $transferred,
        float $percent,
        int $bytesPerSecond
    ): void {
        self::put([
            'status' => 'downloading',
            'error' => null,
            'progress' => [
                'total_bytes' => $total,
                'delta_bytes' => $delta,
                'transferred_bytes' => $transferred,
                'percent' => $percent,
                'bytes_per_second' => $bytesPerSecond,
            ],
        ]);
    }

    public static function setDownloaded(string $version): void
    {
        self::put([
            'status' => 'downloaded',
            'version' => $version,
            'error' => null,
            'progress' => array_merge(self::get()['progress'], ['percent' => 100.0]),
        ]);
    }

    public static function setFailed(string $message): void
    {
        self::put(['status' => 'failed', 'error' => mb_substr($message, 0, 1000)]);
    }

    public static function setCancelled(?string $version = null): void
    {
        self::put(array_filter([
            'status' => 'cancelled',
            'version' => $version,
            'error' => null,
        ], fn ($value) => $value !== null));
    }

    public static function clear(): void
    {
        Cache::forget(self::KEY);
    }

    private static function put(array $changes): void
    {
        Cache::forever(self::KEY, array_replace_recursive(self::get(), $changes));
    }

    private static function defaults(): array
    {
        return [
            'status' => 'none',
            'version' => null,
            'notes' => null,
            'error' => null,
            'progress' => self::emptyProgress(),
        ];
    }

    private static function emptyProgress(): array
    {
        return [
            'total_bytes' => 0,
            'delta_bytes' => 0,
            'transferred_bytes' => 0,
            'percent' => 0.0,
            'bytes_per_second' => 0,
        ];
    }
}
