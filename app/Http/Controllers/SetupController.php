<?php

namespace App\Http\Controllers;

use App\Support\Branch;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SetupController — «شاشة إعداد أول تشغيل» على جهاز الفرع.
 *
 * تجعل نسخة .exe واحدة تكفي لكل الفروع: المستخدم يُدخل كود الفرع + رابط السيرفر +
 * التوكن مرة واحدة، فيسجّل التطبيق نفسه عند السيرفر (POST /api/sync/register) ويستلم
 * branch_id ثابتاً يُحفظ في المخزن الدائم (app_settings) — لا في env المخبوز ولا الكاش.
 */
class SetupController extends Controller
{
    public function show()
    {
        // مُسجَّل بالفعل (أو هذا هو السيرفر) → لا داعي للإعداد.
        if (Branch::isRegistered()) {
            return redirect('/');
        }

        return view('setup', [
            'serverUrl' => Settings::get('sync.server_url', ''),
            'code'      => Settings::get('branch.code', ''),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'           => 'required|string|max:16',
            'name'           => 'nullable|string|max:255',
            'server_url'     => 'required|url',
            'token'          => 'required|string|max:80',
            'owner_login'    => 'required|string|max:255',
            'owner_password' => 'required|string',
        ]);

        $serverUrl = rtrim($data['server_url'], '/');
        $code      = strtoupper(trim($data['code']));

        // سجّل الفرع عند السيرفر (مع بيانات مالك الصيدلية) واحصل على branch_id ثابت.
        try {
            $response = Http::withHeaders(['X-Sync-Token' => $data['token']])
                ->timeout(30)
                ->acceptJson()
                ->post($serverUrl . '/api/sync/register', [
                    'code'           => $code,
                    'name'           => $data['name'] ?? null,
                    'owner_login'    => $data['owner_login'],
                    'owner_password' => $data['owner_password'],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Branch registration connection failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'تعذّر الاتصال بالسيرفر: ' . $e->getMessage());
        }

        if (! $response->successful() || ! $response->json('success')) {
            $msg = $response->json('message');
            if (empty($msg)) {
                $msg = $response->status() === 401
                    ? 'التوكن أو بيانات دخول الصيدلية غير صحيحة.'
                    : 'فشل التسجيل عند السيرفر.';
            }
            return back()->withInput()->with('error', $msg);
        }

        $branchId = $response->json('branch_id');
        if (empty($branchId)) {
            return back()->withInput()->with('error', 'استجابة تسجيل غير صالحة من السيرفر.');
        }

        // احفظ الهوية والإعدادات بشكل دائم.
        Settings::set('branch.id', $branchId);
        Settings::set('branch.code', $code);
        Settings::set('branch.name', $data['name'] ?? null);
        Settings::set('sync.server_url', $serverUrl);
        Settings::set('sync.token', $data['token']);
        Settings::set('sync.enabled', '1');
        Settings::set('registered_at', now()->toDateTimeString());

        Log::info('Branch registered locally', ['code' => $code, 'branch_id' => $branchId]);

        // هيّئ config لهذا الطلب فوراً (boot حدث قبل كتابة Settings فكانت فارغة).
        config([
            'sync.branch_id'   => $branchId,
            'sync.branch_code' => $code,
            'sync.server_url'  => $serverUrl,
            'sync.token'       => $data['token'],
            'sync.enabled'     => true,
        ]);

        // أول مزامنة (سحب الكتالوج + حساب الصيدلية وموظفيها للعمل offline) —
        // لا نفشل الإعداد لو تعثّرت، الجدولة الدورية تعيدها.
        try {
            \Illuminate\Support\Facades\Artisan::call('sync:pull');
        } catch (\Throwable $e) {
            Log::warning('Initial pull after setup failed', ['error' => $e->getMessage()]);
        }

        return redirect('/')->with('success', 'تم تسجيل الفرع «' . $code . '» بنجاح.');
    }
}
