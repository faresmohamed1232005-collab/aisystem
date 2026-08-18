<?php

namespace App\Support;

/**
 * DesktopWindows — القائمة البيضاء للشاشات التي يجوز فتحها في نافذة مستقلة على الديسكتوب.
 *
 * مصدر واحد يشترك فيه المتحكّم (تحقّق الإدخال) والواجهة (إظهار زر «نافذة جديدة»). كلها
 * شاشات GET بلا params مطلوبة، وآمنة للفتح مستقلةً. التصريح النهائي يبقى على المسار نفسه
 * (auth + @can) عند تحميل النافذة الجديدة.
 */
class DesktopWindows
{
    /** @var array<int,string> أسماء مسارات مسموح فتحها في نافذة جديدة. */
    public const POPPABLE = [
        'sales.create',
        'products.index',
        'dashboard',
        'pending.index',
        'sales.index',
        'purchases.index',
        'purchases.create',
        'customers.index',
        'suppliers.index',
        'stock-transfers.index',
    ];

    public static function isPoppable(?string $name): bool
    {
        return $name !== null && in_array($name, self::POPPABLE, true);
    }
}
