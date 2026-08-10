<?php

namespace App\Support;

class Runtime
{
    /** هل التطبيق يعمل داخل حاوية NativePHP لسطح المكتب؟ */
    public static function isDesktop(): bool
    {
        return filter_var(env('NATIVEPHP_RUNNING', false), FILTER_VALIDATE_BOOL)
            || (bool) config('nativephp-internal.running');
    }
}
