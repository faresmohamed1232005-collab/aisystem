<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * هوية الفرع (Branch identity) — أساس المزامنة متعددة الفروع.
 *
 * كل تثبيت (نسخة desktop على جهاز فرع، أو السيرفر المركزي) له معرّف فرع ثابت.
 * مصدر الهوية بالترتيب:
 *   1) config('sync.branch_id') — يُهيَّأ من Settings الدائم (شاشة الإعداد) في
 *      AppServiceProvider، أو من env BRANCH_ID على السيرفر (BRANCH_ID=server).
 *   2) Settings الدائم مباشرة (app_settings) — يبقى ثابتاً رغم مسح الكاش.
 *   3) fallback: توليد ULID ثابت وحفظه في Settings (لا في الكاش المتقلّب).
 *
 * ملاحظة: نسخة .exe واحدة تكفي لكل الفروع — الهوية تُخصَّص وقت التشغيل عبر شاشة
 * الإعداد (تسجيل عند السيرفر) لا وقت البناء.
 *
 * code: بادئة قصيرة تُستخدم في ترقيم الفواتير (مثل A, B, C) لمنع التصادم بين الفروع.
 */
class Branch
{
    /** معرّف الفرع الثابت لهذا التثبيت. */
    public static function id(): string
    {
        $configured = config('sync.branch_id') ?: env('BRANCH_ID');
        if (!empty($configured)) {
            return (string) $configured;
        }

        // مصدر دائم (شاشة الإعداد تكتبه هنا عادةً).
        $stored = Settings::get('branch.id');
        if (!empty($stored)) {
            return (string) $stored;
        }

        // fallback أخير: ولّد معرّفاً ثابتاً واحفظه دائماً (Settings لا Cache).
        $new = 'br_' . Str::ulid();
        Settings::set('branch.id', $new);
        return $new;
    }

    /** بادئة الفرع المستخدمة في ترقيم الفواتير (مثل "A"). */
    public static function code(): string
    {
        $code = config('sync.branch_code') ?: env('BRANCH_CODE') ?: Settings::get('branch.code');
        if (!empty($code)) {
            return strtoupper((string) $code);
        }

        // اشتقاق بادئة قصيرة من معرّف الفرع كحل احتياطي.
        return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', self::id()), -2)) ?: 'BR';
    }

    /** هل هذا التثبيت هو السيرفر المركزي (master)؟ */
    public static function isServer(): bool
    {
        return self::id() === (config('sync.server_branch_id') ?: 'server');
    }

    /**
     * هل الفرع مُسجَّل وجاهز للمزامنة؟ (هوية + رابط سيرفر + توكن محفوظة دائماً.)
     * السيرفر المركزي يُعدّ «مُسجَّلاً» دائماً (يُدار عبر env لا شاشة الإعداد).
     */
    public static function isRegistered(): bool
    {
        // لا تستدعِ id() هنا: شاشة الإعداد غير المسجّلة يجب ألا تولّد هوية عشوائية
        // قبل نجاح التسجيل، خصوصاً عند وجود حارس إعادة التهيئة.
        $configured = config('sync.branch_id') ?: env('BRANCH_ID') ?: Settings::get('branch.id');
        if ($configured === (config('sync.server_branch_id') ?: 'server')) {
            return true;
        }

        return ! empty($configured)
            && Settings::has('sync.server_url')
            && Settings::has('sync.token');
    }
}
