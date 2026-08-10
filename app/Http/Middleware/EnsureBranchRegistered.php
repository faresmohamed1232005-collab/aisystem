<?php

namespace App\Http\Middleware;

use App\Support\Branch;
use App\Support\Runtime;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureBranchRegistered — يفرض «شاشة إعداد أول تشغيل» على جهاز الفرع.
 *
 * على الديسكتوب (NativePHP) فقط: إن لم يكن الفرع مُسجَّلاً بعد (لا هوية/رابط/توكن)،
 * نوجّه كل الطلبات إلى /setup. على الويب/السيرفر لا نفرض شيئاً (يُدار عبر env).
 */
class EnsureBranchRegistered
{
    public function handle(Request $request, Closure $next): Response
    {
        // نفرض الإعداد على الديسكتوب غير المُسجَّل فقط.
        if (Runtime::isDesktop() && ! Branch::isRegistered()) {
            // اسمح بمسارات الإعداد نفسها والأصول والفحص الصحي حتى لا ندخل حلقة توجيه.
            if ($request->is('setup', 'setup/*', 'settings/diagnostics', 'settings/diagnostics/*', 'up', 'build/*', 'css/*', 'js/*', 'images/*', 'favicon.ico')) {
                return $next($request);
            }

            return redirect('/setup');
        }

        return $next($request);
    }
}
