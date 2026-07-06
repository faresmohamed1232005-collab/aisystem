<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Actor — الفاعل الحالي في الطلب (Phase 3 RBAC).
 *
 * نظام الدخول: Auth::id() دائماً = المالك (tenant). لو دخل موظف (sub_user) فبياناته
 * في session('sub_user'). هذا الـ helper يوحّد قراءة هوية الفاعل ودوره وصلاحياته
 * بغضّ النظر عن كونه المالك أو موظفاً — تُستخدمها الـ Gates والـ Audit.
 */
class Actor
{
    /** هل الفاعل هو المالك (لا موظف في الجلسة)؟ */
    public static function isOwner(): bool
    {
        return Auth::check() && ! session()->has('sub_user');
    }

    /** دور الفاعل الحالي. المالك = OWNER، وإلا دور الموظف من الجلسة. */
    public static function role(): string
    {
        if (self::isOwner()) {
            return Roles::OWNER;
        }
        return (string) (session('sub_user.role') ?: Roles::PHARMACIST);
    }

    /** هل يملك الفاعل هذه الصلاحية؟ */
    public static function can(string $ability): bool
    {
        if (! Auth::check()) {
            return false;
        }
        return Roles::roleCan(self::role(), $ability);
    }

    /** اسم الفاعل للعرض/السجل. */
    public static function name(): ?string
    {
        if (self::isOwner()) {
            return optional(Auth::user())->name;
        }
        return session('sub_user.name');
    }

    /** معرّف الموظف الحالي (null لو المالك) — لسجل التدقيق. */
    public static function subUserId(): ?int
    {
        return self::isOwner() ? null : session('sub_user.id');
    }
}
