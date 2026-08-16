<?php

namespace App\Http\Middleware;

use App\Support\Branch;
use App\Support\Runtime;
use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureBranchRegistered — يفرض «شاشة إعداد أول تشغيل» ثم «إكمال أول مزامنة» على جهاز الفرع.
 *
 * على الديسكتوب (NativePHP) فقط:
 *   1) إن لم يكن الفرع مُسجَّلاً (لا هوية/رابط/توكن) → كل الطلبات إلى /setup.
 *   2) إن كان مُسجَّلاً لكن أول مزامنة لم تكتمل (initial_setup_completed_at غير مضبوط) →
 *      كل الطلبات إلى /setup/sync، فلا يدخل المستخدم التطبيق بقاعدة نصف محمّلة.
 * على الويب/السيرفر لا نفرض شيئاً (يُدار عبر env).
 */
class EnsureBranchRegistered
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Runtime::isDesktop()) {
            // مسارات الإعداد نفسها والتشخيص والأصول والفحص الصحي — مسموحة دائماً (منعاً لحلقة توجيه،
            // ولإتاحة إعادة الضبط المحلي عند القفل).
            $allowed = $request->is(
                'setup', 'setup/*', 'settings/diagnostics', 'settings/diagnostics/*',
                // تحديث التطبيق أداة صيانة — يبقى متاحاً حتى قبل اكتمال الإعداد (قد يلزم
                // تحديث النسخة لإصلاح مشكلة إعداد)؛ صلاحية المالك تُفرض داخل المتحكّم.
                'update/*',
                'up', 'build/*', 'css/*', 'js/*', 'images/*', 'favicon.ico'
            );

            if (! Branch::isRegistered()) {
                return $allowed ? $next($request) : redirect('/setup');
            }

            // مُسجَّل لكن أول مزامنة لم تكتمل بعد → ابقَ في شاشة التنزيل حتى 100%.
            if (! Settings::has('initial_setup_completed_at') && ! $allowed) {
                return redirect()->route('setup.sync.show');
            }
        }

        return $next($request);
    }
}
