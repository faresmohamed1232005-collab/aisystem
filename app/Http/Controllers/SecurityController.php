<?php

namespace App\Http\Controllers;

use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * إعدادات أمان حساب المالك (Phase 3C) — تفعيل/تعطيل المصادقة الثنائية (TOTP).
 *
 * متاح للمالك فقط (الموظفون يدخلون بحساب المالك؛ إدارة 2FA حساسة). السرّ يُخزَّن
 * مشفّراً (cast encrypted) ولا يُفعَّل إلا بعد تأكيد أول كود من تطبيق المصادقة.
 */
class SecurityController extends Controller
{
    public function show()
    {
        $user   = Auth::user();
        $secret = session('2fa_setup_secret'); // يظهر فقط أثناء الإعداد

        $uri = $secret
            ? Totp::uri($secret, $user->email ?? $user->username ?? 'user', config('app.name', 'Pharmacy'))
            : null;

        return view('security.index', [
            'enabled' => (bool) $user->two_factor_enabled,
            'secret'  => $secret,
            'uri'     => $uri,
        ]);
    }

    /** توليد سرّ جديد وبدء الإعداد (لا يُفعَّل قبل تأكيد كود). */
    public function enable(Request $request)
    {
        abort_if(session()->has('sub_user'), 403, 'إدارة الأمان للمالك فقط.');

        $user = Auth::user();
        $secret = Totp::generateSecret();
        $user->update(['two_factor_secret' => $secret, 'two_factor_enabled' => false]);

        // نضع السرّ في الجلسة مؤقتاً لعرضه في صفحة الإعداد فقط.
        return redirect()->route('security.index')->with('2fa_setup_secret', $secret);
    }

    /** تأكيد أول كود لتفعيل 2FA. */
    public function confirm(Request $request)
    {
        abort_if(session()->has('sub_user'), 403);
        $request->validate(['code' => 'required|string'], ['code.required' => 'أدخل الكود']);

        $user = Auth::user();
        if (empty($user->two_factor_secret) || ! Totp::verify($user->two_factor_secret, $request->code)) {
            return back()->with('2fa_setup_secret', $user->two_factor_secret)
                ->withErrors(['code' => 'كود غير صحيح، حاول مرة أخرى.']);
        }

        $user->update(['two_factor_enabled' => true]);

        return redirect()->route('security.index')->with('success', 'تم تفعيل المصادقة الثنائية ✅');
    }

    /** تعطيل 2FA (يتطلب كلمة المرور). */
    public function disable(Request $request)
    {
        abort_if(session()->has('sub_user'), 403);
        $request->validate(['password' => 'required|string'], ['password.required' => 'كلمة المرور مطلوبة']);

        $user = Auth::user();
        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'كلمة المرور غير صحيحة.']);
        }

        $user->update(['two_factor_secret' => null, 'two_factor_enabled' => false]);

        return redirect()->route('security.index')->with('success', 'تم تعطيل المصادقة الثنائية.');
    }
}
