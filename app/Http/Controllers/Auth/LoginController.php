<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SubUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login    = $request->input('login');
        $password = $request->input('password');

        // ══ Super Admin ══
        if ($login === config('superadmin.email')) {
            if (Auth::attempt(['email' => $login, 'password' => $password], $request->boolean('remember'))) {
                $request->session()->regenerate();
                session()->forget('sub_user');
                return redirect()->route('super.admin.index');
            }
            return back()->withErrors(['login' => 'بيانات الدخول غلط.'])->withInput();
        }

        // ══ Admin عادي ══
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user  = User::where($field, $login)->first();

        if ($user) {
            // ✅ تحقق من الحظر أولاً قبل أي حاجة
            if (! $user->is_approved) {
                return back()
                    ->withErrors([
                        'login' => '🚫 تم حظر هذا الحساب. لفك الحظر تواصل مع خدمة العملاء.',
                    ])
                    ->withInput();
            }

            if (Auth::attempt([$field => $login, 'password' => $password], $request->boolean('remember'))) {
                // مصادقة ثنائية مفعّلة → لا نُكمل الدخول قبل التحقق من الكود.
                if ($user->two_factor_enabled && ! empty($user->two_factor_secret)) {
                    Auth::logout();
                    $request->session()->put('2fa:pending', $user->id);
                    $request->session()->put('2fa:remember', $request->boolean('remember'));
                    return redirect()->route('login.2fa');
                }

                $request->session()->regenerate();
                session()->forget('sub_user');
                return redirect()->route('dashboard');
            }

            // كلمة السر غلط
            return back()
                ->withErrors(['login' => 'بيانات الدخول غلط، حاول تاني.'])
                ->withInput();
        }

        // ══ Sub User ══
        $subUser = SubUser::where('email', $login)
            ->where('is_active', true)
            ->first();

        if ($subUser && Hash::check($password, $subUser->password)) {
            // ✅ تحقق من حالة الحساب الرئيسي (owner) كمان
            $owner = User::find($subUser->owner_id);
            if ($owner && ! $owner->is_approved) {
                return back()
                    ->withErrors([
                        'login' => '🚫 تم حظر هذا الحساب. لفك الحظر تواصل مع خدمة العملاء.',
                    ])
                    ->withInput();
            }

            Auth::loginUsingId($subUser->owner_id, $request->boolean('remember'));
            $request->session()->regenerate();
            session([
                'sub_user' => [
                    'id'    => $subUser->id,
                    'name'  => $subUser->name,
                    'email' => $subUser->email,
                    'role'  => $subUser->role,
                ]
            ]);
            return redirect()->route('dashboard');
        }

        return back()
            ->withErrors(['login' => 'بيانات الدخول غلط، حاول تاني.'])
            ->withInput();
    }

    /** شاشة إدخال كود المصادقة الثنائية (بين كلمة المرور والدخول الكامل). */
    public function showTwoFactor(Request $request)
    {
        if (! $request->session()->has('2fa:pending')) {
            return redirect()->route('login');
        }
        return view('auth.two-factor');
    }

    /** تحقّق من كود 2FA وأكمل الدخول. */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate(['code' => 'required|string'], ['code.required' => 'أدخل الكود']);

        $pendingId = $request->session()->get('2fa:pending');
        if (! $pendingId) {
            return redirect()->route('login');
        }

        $user = User::find($pendingId);
        if (! $user || empty($user->two_factor_secret) || ! \App\Support\Totp::verify($user->two_factor_secret, $request->code)) {
            return back()->withErrors(['code' => 'كود غير صحيح، حاول مرة أخرى.']);
        }

        Auth::loginUsingId($user->id, (bool) $request->session()->get('2fa:remember'));
        $request->session()->forget(['2fa:pending', '2fa:remember']);
        $request->session()->regenerate();
        session()->forget('sub_user');

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        session()->forget('sub_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}