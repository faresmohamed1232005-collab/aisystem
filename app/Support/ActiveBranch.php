<?php

namespace App\Support;

use App\Models\BranchModel;
use Illuminate\Support\Facades\Auth;

/**
 * ActiveBranch — «الفرع العامل» لهذا الطلب (Phase 2ب — إدارة مركزية).
 *
 * ينفصل عن App\Support\Branch (هوية هذا التثبيت للمزامنة/التدقيق) عن سؤال «أي فرع
 * تملكه بيانات العمل التي أُنشئها/أعرضها الآن؟»:
 *   - على الديسكتوب: الجهاز فرع واحد ثابت، فالفرع العامل = Branch::id() دائماً (لا تغيير).
 *   - على الموقع المركزي: المالك يختار فرعاً يشتغل عليه (يُخزَّن في الجلسة)، فيُوسَم به
 *     ما يُنشأ (شراء/مخزون) ويُقيَّد به العرض — فيسحبه ديسكتوب ذلك الفرع لاحقاً.
 *   - على الموقع بدون اختيار: يرجع لـ Branch::id() (= 'server') — سلوك اليوم تماماً.
 *
 * الهوية الجهازية للمزامنة (SyncPush/Pull/Manifest) والتدقيق تبقى على Branch::id()،
 * فلا تتأثر بالفرع العامل.
 */
class ActiveBranch
{
    /** مفتاح الجلسة الذي يحمل الفرع الذي يعمل عليه المالك على الموقع. */
    public const SESSION_KEY = 'active_branch_id';

    /** معرّف الفرع العامل (branch_id) لهذا الطلب. */
    public static function id(): string
    {
        // الديسكتوب: فرع واحد ثابت — لا سياق فرع نشط.
        if (Runtime::isDesktop()) {
            return Branch::id();
        }

        // الموقع: الفرع الذي اختاره المالك (إن وُجد واختياره مملوك له).
        $selected = session(self::SESSION_KEY);
        if (! empty($selected)) {
            return (string) $selected;
        }

        // بدون اختيار: هوية هذا التثبيت (= 'server' مركزياً) — افتراضي متوافق مع القديم.
        return Branch::id();
    }

    /** هل يعمل المالك على فرع محدّد (وليس المركز الافتراضي)؟ */
    public static function isSelected(): bool
    {
        return ! Runtime::isDesktop() && ! empty(session(self::SESSION_KEY));
    }

    /** فروع المالك الحالي (لقائمة المبدّل على الموقع). */
    public static function ownerBranches()
    {
        if (! Auth::check()) {
            return collect();
        }

        return BranchModel::where('user_id', Auth::id())->orderBy('code')->get();
    }
}
