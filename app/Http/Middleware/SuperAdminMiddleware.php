<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $adminEmail = config('superadmin.email');

        if (!auth()->check() || auth()->user()->email !== $adminEmail) {
            abort(403, 'غير مصرح');
        }

        return $next($request);
    }
}