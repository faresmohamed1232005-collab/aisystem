<?php

namespace App\Support;

/**
 * الأدوار والصلاحيات (RBAC خفيف — Phase 3).
 *
 * نظام أدوار مخصّص بدل spatie: الأدوار تُخزَّن كنص في sub_users.role وتُزامَن مع بقية
 * الموظفين (server-master). المالك (User/tenant) دوره ضمنياً OWNER بكل الصلاحيات.
 *
 * الصلاحيات (abilities) تُفحص عبر Gates مسجّلة من هذه المصفوفة — انظر
 * AppServiceProvider::registerRoleGates و App\Support\Actor.
 */
class Roles
{
    public const OWNER          = 'owner';
    public const AREA_MANAGER   = 'area_manager';
    public const BRANCH_MANAGER = 'branch_manager';
    public const PHARMACIST     = 'pharmacist';
    public const ACCOUNTANT     = 'accountant';

    /** الأدوار المسموح بها لموظفٍ (sub_user) — المالك ليس منها (ضمني). */
    public const ASSIGNABLE = [
        self::AREA_MANAGER,
        self::BRANCH_MANAGER,
        self::PHARMACIST,
        self::ACCOUNTANT,
    ];

    public const LABELS = [
        self::OWNER          => 'المالك',
        self::AREA_MANAGER   => 'مدير منطقة',
        self::BRANCH_MANAGER => 'مدير فرع',
        self::PHARMACIST     => 'صيدلي',
        self::ACCOUNTANT     => 'محاسب',
    ];

    /** كل الصلاحيات المعرّفة (تُسجَّل كلها كـ Gates). */
    public const ABILITIES = [
        'manage_branches',    // تحرير بيانات/صلاحيات الفروع
        'create_transfers',   // إنشاء/إرسال تحويل مخزون
        'receive_transfers',  // استلام/رفض تحويل وارد
        'manage_contracts',   // تعاقدات/تأمين/مطالبات
        'manage_employees',   // موظفون + مصروفات
        'manage_sub_users',   // إدارة صلاحيات المستخدمين
        'view_reports',       // تقارير المبيعات/المشتريات/التوقّع
        'manage_pricing',     // تعديل أسعار/مخزون المنتجات
        'manage_purchases',   // فواتير الشراء + مرتجعاتها
        'do_sales',           // البيع + مرتجعات البيع + الطلبات المعلّقة
        'manage_customers',   // العملاء + الموردين
        'manage_stock',       // إضافة للمخزن + الجرد
        'view_audit',         // عرض سجل التدقيق
    ];

    /**
     * مصفوفة الأدوار → الصلاحيات. المالك (OWNER) غير مذكور لأنه يملك كل شيء ضمنياً
     * (يُفحص في Actor). أي دور غير معروف = بلا صلاحيات.
     */
    public const MATRIX = [
        self::AREA_MANAGER => [
            'manage_branches', 'create_transfers', 'receive_transfers', 'manage_contracts',
            'manage_employees', 'view_reports', 'manage_pricing', 'manage_purchases',
            'do_sales', 'manage_customers', 'manage_stock', 'view_audit',
        ],
        self::BRANCH_MANAGER => [
            'create_transfers', 'receive_transfers', 'view_reports', 'manage_pricing',
            'manage_purchases', 'do_sales', 'manage_customers', 'manage_stock',
        ],
        self::PHARMACIST => [
            'do_sales', 'manage_customers', 'manage_stock', 'receive_transfers',
        ],
        self::ACCOUNTANT => [
            'view_reports', 'manage_contracts', 'manage_employees', 'manage_customers', 'view_audit',
        ],
    ];

    public static function label(?string $role): string
    {
        return self::LABELS[$role] ?? ($role ?? '—');
    }

    /** هل يملك هذا الدور هذه الصلاحية؟ (المالك يملك كل شيء.) */
    public static function roleCan(string $role, string $ability): bool
    {
        if ($role === self::OWNER) {
            return true;
        }
        return in_array($ability, self::MATRIX[$role] ?? [], true);
    }
}
