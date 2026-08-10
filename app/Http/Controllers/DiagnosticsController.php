<?php

namespace App\Http\Controllers;

use App\Services\Diagnostics\PendingFactoryReset;
use App\Services\Diagnostics\RecoveryService;
use App\Services\Diagnostics\ReportAggregator;
use App\Services\Sync\SyncPullService;
use App\Services\Sync\SyncRunner;
use App\Services\Sync\SyncStatus;
use App\Services\UpdateState;
use App\Support\Actor;
use App\Support\Branch;
use App\Support\Runtime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class DiagnosticsController extends Controller
{
    public function page(ReportAggregator $aggregator, SyncPullService $pull): View
    {
        $payload = $this->payload($aggregator, auth()->check());

        // سجل المزامنة غير موجود أصلاً في حمولة الضيف؛ القيمة الفارغة للعرض فقط.
        return view('settings.diagnostics', $payload + [
            'logs' => [],
            'pullableTables' => $pull->pullableTables(),
        ]);
    }

    public function status(ReportAggregator $aggregator): JsonResponse
    {
        return response()->json($this->payload($aggregator, auth()->check()));
    }

    public function sync(SyncRunner $runner): RedirectResponse
    {
        $this->authorizeRecovery();
        $result = $runner->run(manual: true);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function repair(Request $request, RecoveryService $recovery, SyncPullService $pull, SyncRunner $runner): RedirectResponse
    {
        abort_unless(Runtime::isDesktop() && config('database.default') === 'sqlite' && Branch::isRegistered(), 404);
        $data = $request->validate([
            'owner_login' => ['required', 'string', 'max:255'],
            'owner_password' => ['required', 'string'],
        ]);

        try {
            $result = $recovery->repairSync($data['owner_login'], $data['owner_password'], $pull, $runner);
            $message = $result['sync']['success']
                ? 'تم إصلاح حساب الدخول والمزامنة بأمان. يمكنك الدخول بنفس بيانات حساب الويب.'
                : 'تم إصلاح حساب الدخول بأمان، لكن بقية المزامنة لم تكتمل. ادخل بحساب الويب ثم أعد تشغيل المزامنة من مركز التشخيص.';

            return redirect()->route('login')->with($result['sync']['success'] ? 'success' : 'warning', $message);
        } catch (Throwable $e) {
            return back()->with('error', $this->redact($e->getMessage()));
        }
    }

    public function reconfigure(Request $request, RecoveryService $recovery): RedirectResponse
    {
        abort_unless(Runtime::isDesktop() && config('database.default') === 'sqlite' && Branch::isRegistered(), 404);
        $data = $request->validate([
            'owner_login' => ['required', 'string', 'max:255'],
            'owner_password' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:'.RecoveryService::DISCONNECT_PHRASE],
        ]);

        try {
            $recovery->disconnectWithCentralOwner($data['owner_login'], $data['owner_password']);
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('setup.show')->with('success', 'تم حفظ نسخة احتياطية وفصل الاتصال. أدخل نفس بيانات الفرع والمالك لإعادة الإعداد.');
        } catch (Throwable $e) {
            return back()->with('error', $this->redact($e->getMessage()));
        }
    }

    public function factoryReset(Request $request, RecoveryService $recovery, PendingFactoryReset $pending): RedirectResponse
    {
        abort_unless(Runtime::isDesktop() && config('database.default') === 'sqlite' && Branch::isRegistered(), 404);
        $data = $request->validate([
            'owner_login' => ['required', 'string', 'max:255'],
            'owner_password' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:'.RecoveryService::FACTORY_RESET_PHRASE],
            'acknowledge_pending' => ['accepted'],
        ]);

        try {
            $recovery->scheduleFactoryReset($data['owner_login'], $data['owner_password'], $pending);
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('diagnostics.page')->with('success', 'تم إنشاء نسخة موثوقة وتجهيز إعادة الضبط. أغلق التطبيق بالكامل ثم افتحه مجددًا لبدء الإعداد من الصفر.');
        } catch (Throwable $e) {
            return back()->with('error', $this->redact($e->getMessage()));
        }
    }

    public function backup(RecoveryService $recovery): RedirectResponse
    {
        $this->authorizeRecovery();

        try {
            $backup = $recovery->backup();
            return back()->with('success', 'تم إنشاء نسخة موثوقة. SHA256: '.$backup['sha256']);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function retry(Request $request, RecoveryService $recovery, SyncPullService $pull): RedirectResponse
    {
        $this->authorizeRecovery();
        $data = $request->validate(['table' => ['required', 'string'], 'password' => ['required', 'string']]);
        $this->confirmOwnerPassword($data['password']);

        try {
            $recovery->retryTable($data['table'], $pull);
            return back()->with('success', 'تمت إعادة تهيئة مؤشر الجدول وسحب دفعة واحدة بأمان.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function disconnect(Request $request, RecoveryService $recovery): RedirectResponse
    {
        $this->authorizeRecovery();
        abort_unless(Runtime::isDesktop() && config('database.default') === 'sqlite', 404);
        $data = $request->validate([
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:'.RecoveryService::DISCONNECT_PHRASE],
        ]);
        $this->confirmOwnerPassword($data['password']);

        try {
            $recovery->disconnect();
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('setup.show')->with('success', 'تم فصل الاتصال مع الاحتفاظ بكل بيانات العمل المحلية.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function authorizeRecovery(): void
    {
        abort_unless(Actor::isOwner(), 403);
    }

    private function confirmOwnerPassword(string $password): void
    {
        if (! Hash::check($password, auth()->user()->password)) {
            abort(422, 'كلمة مرور المالك غير صحيحة.');
        }
    }

    private function payload(ReportAggregator $aggregator, bool $authenticated): array
    {
        $payload = [
            'report' => $aggregator->report(),
            'update' => UpdateState::get(),
        ];

        // علاّمة بنيوية (آمنة للعرض) يستخدمها القالب بلا اعتماد على نص الرسالة الخام.
        $payload['report']['sync']['runtime']['identity_conflict'] = $this->hasIdentityConflict($payload['report']);

        if ($authenticated) {
            $payload['logs'] = $this->safeLogs();
        } else {
            $payload['report'] = $this->guestReport($payload['report']);
        }

        return $this->sanitize($payload);
    }

    /** أقل قدر مفيد من التشخيص لضيف سطح المكتب، بلا تفاصيل داخلية أو رسائل خام. */
    private function guestReport(array $report): array
    {
        unset(
            $report['application']['environment'],
            $report['application']['php_version'],
            $report['application']['laravel_version']
        );

        $message = (string) ($report['sync']['runtime']['message'] ?? '');
        if (str_contains($message, 'تعارض id محفوظ')) {
            $report['sync']['runtime']['message'] = 'تعارض آمن في هوية حساب الدخول المحلي؛ استخدم إصلاح المزامنة لإعادة ربط المالك دون حذف البيانات.';
        } elseif ($report['sync']['runtime']['identity_conflict']) {
            $report['sync']['runtime']['message'] = 'تعارض آمن في هوية حساب الدخول المحلي؛ استخدم إصلاح المزامنة لإعادة ربط المالك دون حذف البيانات.';
        } elseif ($message !== '') {
            $report['sync']['runtime']['message'] = 'تعذّرت آخر محاولة مزامنة. سجّل الدخول أو استخدم خيارات الإصلاح الآمنة.';
        }

        $branchId = $report['registration']['branch_id'] ?? null;
        if (is_string($branchId) && $branchId !== '') {
            $report['registration']['branch_id'] = '••••••' . mb_substr($branchId, -6);
        }

        return $this->omitInternalDetails($report);
    }

    /** هل توقّف Pull لتعارض هوية حساب الدخول بالنفس المحفوظ (وليس عطل شبكة)؟ */
    private function hasIdentityConflict(array $report): bool
    {
        $needle = 'تعارض id محفوظ';
        $runtimeMessage = (string) ($report['sync']['runtime']['message'] ?? '');
        if (str_contains($runtimeMessage, $needle)) {
            return true;
        }

        foreach (($report['sync']['table_progress'] ?? []) as $row) {
            $error = (string) ($row['last_error'] ?? '');
            if (str_contains($error, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function omitInternalDetails(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach (array_keys($value) as $key) {
            if (in_array((string) $key, ['error', 'last_error'], true)) {
                unset($value[$key]);
                continue;
            }

            $value[$key] = $this->omitInternalDetails($value[$key]);
        }

        return $value;
    }

    private function safeLogs(): array
    {
        try {
            if (! Schema::hasTable('sync_logs')) {
                return [];
            }

            return collect(SyncStatus::recentLogs(20))->map(fn ($log) => [
                'direction' => (string) ($log->direction ?? ''),
                'status' => (string) ($log->status ?? ''),
                'rows' => (int) ($log->rows ?? 0),
                'duration_ms' => (int) ($log->duration_ms ?? 0),
                'message' => $this->redact((string) ($log->message ?? '')),
                'created_at' => $log->created_at ?? null,
            ])->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function sanitize(mixed $value, ?string $key = null): mixed
    {
        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                $value[$childKey] = $this->sanitize($childValue, (string) $childKey);
            }

            return $value;
        }

        if (is_string($value)) {
            return $this->redact($value);
        }

        return $value;
    }

    private function redact(string $message): string
    {
        $message = preg_replace('/\b(password|token|secret|key)\s*[=:]\s*[^\s;,]+/i', '$1=[redacted]', $message);
        $message = preg_replace('/Bearer\s+\S+/i', 'Bearer [redacted]', (string) $message);
        $message = preg_replace('/([?&](?:token|key|secret|password)=)[^&\s]+/i', '$1[redacted]', (string) $message);
        $message = preg_replace('#(https?://)[^/@\s]+:[^/@\s]+@#i', '$1[redacted]@', (string) $message);

        return mb_substr((string) $message, 0, 500);
    }
}
