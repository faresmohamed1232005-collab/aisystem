<?php

namespace App\Support;

/**
 * DesktopWindows — الشاشات التي يجوز فتحها في نافذة مستقلة على الديسكتوب.
 *
 * خريطة: اسم المسار => [تسمية عربية، أيقونة] — مصدر واحد يشترك فيه المتحكّم (تحقّق الإدخال
 * عبر keys()) والواجهة (قائمة «نافذة جديدة» المنسدلة). كلها شاشات GET بلا params مطلوبة،
 * وآمنة للفتح مستقلةً. التصريح النهائي يبقى على المسار نفسه (auth + @can) عند تحميل النافذة.
 */
class DesktopWindows
{
    /** @var array<string, array{label:string, icon:string}> */
    public const POPPABLE = [
        'sales.create'          => ['label' => 'بيع جديد', 'icon' => 'fa-cash-register'],
        'purchases.create'      => ['label' => 'فاتورة شراء جديدة', 'icon' => 'fa-file-invoice'],
        'purchases.index'       => ['label' => 'فواتير الشراء', 'icon' => 'fa-file-invoice-dollar'],
        'products.index'        => ['label' => 'المخزن', 'icon' => 'fa-boxes'],
        'sales.index'           => ['label' => 'المبيعات', 'icon' => 'fa-receipt'],
        'pending.index'         => ['label' => 'الطلبات المعلّقة', 'icon' => 'fa-clock'],
        'customers.index'       => ['label' => 'العملاء', 'icon' => 'fa-users'],
        'suppliers.index'       => ['label' => 'الموردين', 'icon' => 'fa-truck'],
        'stock-transfers.index' => ['label' => 'تحويلات المخزون', 'icon' => 'fa-right-left'],
        'dashboard'             => ['label' => 'لوحة التحكم', 'icon' => 'fa-gauge'],
    ];

    /** @return array<int,string> أسماء المسارات المسموح بها (للتحقّق). */
    public static function keys(): array
    {
        return array_keys(self::POPPABLE);
    }

    public static function isPoppable(?string $name): bool
    {
        return $name !== null && array_key_exists($name, self::POPPABLE);
    }
}
