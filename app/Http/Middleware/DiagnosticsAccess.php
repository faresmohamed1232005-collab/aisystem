<?php

namespace App\Http\Middleware;

use App\Support\Runtime;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** يسمح بالتشخيص للمستخدم المسجّل، أو لضيف تطبيق سطح المكتب فقط. */
class DiagnosticsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() || Runtime::isDesktop()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(401, 'Unauthenticated.');
        }

        return redirect()->guest(route('login'));
    }
}
