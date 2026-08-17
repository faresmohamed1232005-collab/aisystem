<?php

namespace App\Http\Controllers;

use App\Services\Diagnostics\RecoveryService;
use App\Support\Branch;
use App\Support\Runtime;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
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

    public function store(Request $request, RecoveryService $recovery)
    {
        $data = $request->validate([
            'code'           => 'required|string|max:16',
            'name'           => 'nullable|string|max:255',
            'device_no'      => 'nullable|integer|min:1',
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
                    'device_no'      => $data['device_no'] ?? null,
                    'owner_login'    => $data['owner_login'],
                    'owner_password' => $data['owner_password'],
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Branch registration connection failed', ['error' => $e->getMessage()]);
            return back()->withInput($request->except(['token', 'owner_password']))
                ->with('error', 'تعذّر الاتصال بالسيرفر. تأكد من الرابط والإنترنت ثم أعد المحاولة.');
        } catch (\Throwable $e) {
            Log::warning('Branch registration failed', ['error' => $e->getMessage()]);
            return back()->withInput($request->except(['token', 'owner_password']))
                ->with('error', 'حدث خطأ أثناء تسجيل الفرع. أعد المحاولة، وإن استمر الخطأ راجع سجل التطبيق.');
        }

        if (! $response->successful() || ! $response->json('success')) {
            $msg = $response->json('message');
            if ($response->status() === 422) {
                $errors = $response->json('errors', []);
                $msg = collect($errors)->flatten()->first() ?: $msg;
            }
            if (empty($msg)) {
                $msg = $response->status() === 401
                    ? 'مفتاح المزامنة أو بيانات دخول الصيدلية غير صحيحة.'
                    : 'فشل التسجيل عند السيرفر.';
            }
            return back()->withInput($request->except(['token', 'owner_password']))->with('error', $msg);
        }

        $branchId = $response->json('branch_id');
        $ownerUuid = $response->json('owner_uuid');
        if (empty($branchId) || empty($ownerUuid)) {
            return back()->withInput($request->except(['token', 'owner_password']))
                ->with('error', 'استجابة تسجيل غير صالحة من السيرفر.');
        }

        if (Settings::get('reconfigure.guard') === '1' && $recovery->hasOperationalRows()) {
            $sameIdentity = hash_equals((string) Settings::get('reconfigure.prior_branch_id'), (string) $branchId)
                && hash_equals((string) Settings::get('reconfigure.prior_owner_uuid'), (string) $ownerUuid);
            if (! $sameIdentity) {
                return back()->withInput($request->except(['token', 'owner_password']))
                    ->with('error', 'رُفض ربط بيانات العمل المحلية بمالك أو فرع مختلف. استخدم نفس هوية الفرع السابقة أو تواصل مع الدعم.');
            }
        }

        // احفظ الهوية والإعدادات بشكل دائم.
        Settings::set('branch.id', $branchId);
        Settings::set('branch.code', $code);
        Settings::set('branch.name', $data['name'] ?? null);
        Settings::set('branch.device_no', $data['device_no'] ?? null);
        Settings::set('branch.owner_uuid', $ownerUuid);
        Settings::set('sync.server_url', $serverUrl);
        Settings::set('sync.token', $data['token']);
        Settings::set('sync.enabled', '1');
        Settings::set('registered_at', now()->toDateTimeString());
        foreach (['reconfigure.guard', 'reconfigure.prior_branch_id', 'reconfigure.prior_owner_uuid', 'reconfigure.backup'] as $key) {
            Settings::forget($key);
        }

        Log::info('Branch registered locally', ['code' => $code, 'branch_id' => $branchId]);

        // هيّئ config لهذا الطلب فوراً (boot حدث قبل كتابة Settings فكانت فارغة).
        config([
            'sync.branch_id'   => $branchId,
            'sync.branch_code' => $code,
            'sync.server_url'  => $serverUrl,
            'sync.token'       => $data['token'],
            'sync.enabled'     => true,
        ]);

        return redirect()->route('setup.sync.show')
            ->with('success', 'تم تسجيل الفرع «' . $code . '» بنجاح. ابدأ تنزيل البيانات.');
    }

    /**
     * إعادة ضبط الاتصال محلياً (offline) — مخرج فكّ القفل عند إعداد أول خاطئ. لا يتصل
     * بالسيرفر ولا يتطلب تسجيل دخول، ويحتفظ ببيانات العمل المحلية. يُحرَس بعبارة تأكيد.
     */
    public function resetConnection(Request $request, RecoveryService $recovery)
    {
        if (! Runtime::isDesktop() || DB::connection()->getDriverName() !== 'sqlite') {
            abort(404);
        }

        $request->validate([
            'confirmation' => ['required', 'string', 'in:' . RecoveryService::DISCONNECT_PHRASE],
        ]);

        try {
            $recovery->resetConnectionLocally();
        } catch (\Throwable $e) {
            Log::warning('Local connection reset failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'تعذّرت إعادة ضبط الاتصال. أعد المحاولة.');
        }

        return redirect()->route('setup.show')
            ->with('success', 'تمت إعادة ضبط الاتصال محلياً مع الاحتفاظ ببياناتك. أدخل بيانات الإعداد الصحيحة.');
    }
}
