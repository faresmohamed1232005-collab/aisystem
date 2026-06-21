<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * هوية الفرع (Branch identity) — أساس المزامنة متعددة الفروع.
 *
 * كل تثبيت (نسخة desktop على جهاز فرع، أو السيرفر المركزي) له معرّف فرع ثابت.
 * يُقرأ من إعداد البيئة BRANCH_ID، وإن لم يوجد يُولَّد ULID ثابت ويُخزَّن (cache) ليبقى
 * ثابتاً عبر الطلبات. على السيرفر المركزي نضبط BRANCH_ID=server (أو ما يناسب).
 *
 * BRANCH_CODE اختياري: بادئة قصيرة تُستخدم في ترقيم الفواتير (مثل A, B, C) لمنع
 * تصادم أرقام الفواتير بين الفروع.
 */
class Branch
{
    private const CACHE_KEY = 'branch.id';

    /** معرّف الفرع الثابت لهذا التثبيت. */
    public static function id(): string
    {
        $configured = config('sync.branch_id') ?: env('BRANCH_ID');
        if (!empty($configured)) {
            return (string) $configured;
        }

        // لم يُضبط في البيئة → ولّد معرّفاً ثابتاً واحفظه (يبقى ثابتاً ما دام الكاش قائماً).
        return Cache::rememberForever(self::CACHE_KEY, fn () => 'br_' . Str::ulid());
    }

    /** بادئة الفرع المستخدمة في ترقيم الفواتير (مثل "A"). افتراضي مشتق من المعرّف. */
    public static function code(): string
    {
        $code = config('sync.branch_code') ?: env('BRANCH_CODE');
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
}
