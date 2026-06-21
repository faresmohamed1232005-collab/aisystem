<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * مصادقة بسيطة لطلبات المزامنة بين الفرع والسيرفر.
 *
 * كل فرع يرسل مفتاحاً سرّياً مشتركاً في الترويسة X-Sync-Token. السيرفر يقارنه بالمفتاح
 * المضبوط في config('sync.token'). مقارنة آمنة زمنياً (hash_equals) لتفادي timing attacks.
 *
 * هذه نقطة بداية بسيطة؛ يمكن الترقية إلى Sanctum (token لكل فرع) لاحقاً دون تغيير الـ API.
 */
class SyncAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('sync.token');
        $provided = (string) $request->header('X-Sync-Token', '');

        if ($expected === '' || !hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized sync request.',
            ], 401);
        }

        return $next($request);
    }
}
