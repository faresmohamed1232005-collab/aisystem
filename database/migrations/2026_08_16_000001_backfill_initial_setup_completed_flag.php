<?php

use App\Support\Branch;
use App\Support\Runtime;
use App\Support\Settings;
use Illuminate\Database\Migrations\Migration;

/**
 * التثبيتات القديمة على الديسكتوب أكملت أول مزامنة قبل وجود علَم
 * initial_setup_completed_at (أُضيف في بوابة الدخول — Part A). بدون هذا الـ backfill
 * كانت بوابة أول-مزامنة ستحوّش تلك الأجهزة المسجّلة العاملة إلى شاشة الإعداد بعد التحديث.
 *
 * على الديسكتوب المسجّل فقط: علّم الإعداد كمكتمل إن لم يكن العلَم مضبوطاً. على الويب أو
 * جهاز غير مسجّل (تثبيت جديد بقاعدة فارغة) لا نفعل شيئاً — فيمرّ التثبيت الجديد بالتدفّق
 * الطبيعي الذي يضبط العلَم عند بلوغ 100٪.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Runtime::isDesktop()) {
            return;
        }

        if (Branch::isRegistered() && ! Settings::has('initial_setup_completed_at')) {
            Settings::set('initial_setup_completed_at', now()->toDateTimeString());
        }
    }

    public function down(): void
    {
        // لا تراجع: إزالة العلَم قد تحوّش جهازاً عاملاً إلى شاشة الإعداد.
    }
};
