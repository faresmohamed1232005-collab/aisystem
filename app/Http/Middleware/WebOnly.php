<?php

namespace App\Http\Middleware;

use App\Support\Runtime;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Runtime::isDesktop()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
