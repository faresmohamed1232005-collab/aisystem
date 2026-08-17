<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Diagnostics\ReportAggregator;
use App\Services\UpdateState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Sync\SyncStatus;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Native\Desktop\Events\AutoUpdater\DownloadProgress;
use Tests\TestCase;

class DiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_progress_event_records_real_download_metrics(): void
    {
        Cache::forget('app.update_state');

        event(new DownloadProgress(1000, 125, 500, 50.0, 250));

        $state = UpdateState::get();
        $this->assertSame('downloading', $state['status']);
        $this->assertSame([
            'total_bytes' => 1000,
            'delta_bytes' => 125,
            'transferred_bytes' => 500,
            'percent' => 50.0,
            'bytes_per_second' => 250,
        ], $state['progress']);
    }

    public function test_diagnostics_reports_healthy_database_with_owner(): void
    {
        User::factory()->create();

        $report = app(ReportAggregator::class)->report();

        $this->assertSame('healthy', $report['database']['status']);
        $this->assertTrue($report['database']['checks']['owner']['present']);
        $this->assertSame('sqlite', $report['database']['driver']);
        $this->assertSame('healthy', $report['database']['checks']['integrity']['status']);
        $this->assertArrayHasKey('pending_push', $report['sync']);
        $this->assertArrayNotHasKey('token', $report['registration']);
    }

    public function test_diagnostics_reports_missing_owner(): void
    {
        $report = app(ReportAggregator::class)->report();

        $this->assertSame('unhealthy', $report['database']['status']);
        $this->assertSame('missing', $report['database']['checks']['owner']['status']);
        $this->assertFalse($report['database']['checks']['owner']['present']);
    }

    public function test_desktop_guest_can_open_diagnostics_even_before_registration(): void
    {
        config(['nativephp-internal.running' => true, 'sync.branch_id' => null]);
        Settings::flush();

        $this->get(route('diagnostics.page'))
            ->assertOk()
            ->assertViewIs('settings.diagnostics')
            ->assertSee('مركز التشخيص والإعدادات');
        $this->getJson(route('diagnostics.status'))->assertOk()->assertJsonStructure(['report', 'update']);
    }

    public function test_desktop_guest_payload_masks_identity_and_omits_internal_details(): void
    {
        config(['nativephp-internal.running' => true]);
        Settings::set('branch.id', 'branch-ABCDEF123456');
        Settings::set('branch.code', 'T');
        Settings::set('sync.server_url', 'https://server.test');
        Settings::set('sync.token', 'guest-secret-token');
        config(['sync.branch_id' => 'branch-ABCDEF123456', 'sync.token' => 'guest-secret-token']);
        SyncStatus::setState('offline', 'SQLSTATE internal path C:\\private\\database.sqlite');
        SyncStatus::record('pull', false, 0, 'private authenticated detail', 15);

        $response = $this->getJson(route('diagnostics.status'))
            ->assertOk()
            ->assertJsonMissingPath('logs')
            ->assertJsonMissingPath('report.application.environment')
            ->assertJsonMissingPath('report.application.php_version')
            ->assertJsonMissingPath('report.application.laravel_version')
            ->assertJsonPath('report.sync.runtime.message', 'تعذّرت آخر محاولة مزامنة. سجّل الدخول أو استخدم خيارات الإصلاح الآمنة.')
            ->assertJsonPath('report.registration.branch_id', '••••••123456');

        $response->assertDontSee('branch-ABCDEF123456')
            ->assertDontSee('private authenticated detail')
            ->assertDontSee('SQLSTATE internal path');
    }

    public function test_guest_identity_conflict_is_flagged_after_message_is_sanitized(): void
    {
        config(['nativephp-internal.running' => true]);
        Settings::set('branch.id', 'br_TEST');
        Settings::set('branch.code', 'T');
        Settings::set('sync.server_url', 'https://server.test');
        Settings::set('sync.token', 'guest-secret-token');
        config(['sync.branch_id' => 'br_TEST', 'sync.token' => 'guest-secret-token']);

        DB::table('sync_table_progress')->insert([
            'table_name' => 'users', 'direction' => 'pull', 'sync_mode' => 'initial', 'status' => 'failed',
            'last_error' => '01SOMEUUID: تعارض id محفوظ: users#1',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->getJson(route('diagnostics.status'))
            ->assertOk()
            ->assertJsonPath('report.sync.runtime.identity_conflict', true)
            ->assertJsonPath('report.sync.runtime.message', 'تعارض آمن في هوية حساب الدخول المحلي؛ استخدم إصلاح المزامنة لإعادة ربط المالك دون حذف البيانات.');
    }

    public function test_authenticated_payload_keeps_safe_useful_detail(): void
    {
        config(['nativephp-internal.running' => false]);
        $user = User::factory()->create();
        SyncStatus::setState('offline', 'اتصال السيرفر متوقف');
        SyncStatus::record('pull', false, 4, 'تعذّر الاتصال بالسيرفر', 25);

        $this->actingAs($user)->getJson(route('diagnostics.status'))
            ->assertOk()
            ->assertJsonPath('report.application.environment', 'testing')
            ->assertJsonPath('report.sync.runtime.message', 'اتصال السيرفر متوقف')
            ->assertJsonPath('logs.0.rows', 4)
            ->assertJsonPath('logs.0.message', 'تعذّر الاتصال بالسيرفر');
    }

    public function test_web_guest_is_redirected_and_json_is_denied(): void
    {
        config(['nativephp-internal.running' => false]);

        $this->get(route('diagnostics.page'))->assertRedirect(route('login'));
        $this->getJson(route('diagnostics.status'))->assertUnauthorized();
    }

    public function test_authenticated_user_can_open_diagnostics_and_sidebar_links_to_it(): void
    {
        config(['nativephp-internal.running' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('diagnostics.page'))->assertOk();
        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('diagnostics.page'), false);
    }

    public function test_desktop_entry_screens_link_to_diagnostics(): void
    {
        config(['nativephp-internal.running' => true, 'sync.branch_id' => null]);
        Settings::flush();
        $this->get(route('setup.show'))->assertSee(route('diagnostics.page'), false);

        Settings::set('branch.id', 'br_TEST');
        Settings::set('branch.code', 'T');
        Settings::set('sync.server_url', 'https://server.test');
        Settings::set('sync.token', 'test-secret-token');
        config(['sync.branch_id' => 'br_TEST']);

        // شاشة التنزيل (أثناء أول مزامنة) تربط بالتشخيص — متاحة قبل ضبط العلَم.
        $this->get(route('setup.sync.show'))->assertSee(route('diagnostics.page'), false);

        // بعد اكتمال أول مزامنة تصبح شاشة الدخول متاحة وتربط بالتشخيص + مخرج إعادة التهيئة
        // الأوفلاين (فكّ القفل لو تعذّر الدخول — يعالج بقاء البيانات بعد إلغاء التثبيت).
        Settings::set('initial_setup_completed_at', now()->toDateTimeString());
        $this->get(route('login'))
            ->assertSee(route('diagnostics.page'), false)
            ->assertSee(route('setup.reset-connection'), false);
    }

    public function test_update_actions_are_available_only_to_desktop_owner(): void
    {
        UpdateState::setAvailable('9.9.9');
        $owner = User::factory()->create();

        config(['nativephp-internal.running' => false]);
        $this->actingAs($owner)->postJson(route('update.check'))->assertNotFound();
        $this->actingAs($owner)->postJson(route('update.download'))->assertNotFound();

        config(['nativephp-internal.running' => true]);
        $this->actingAs($owner)->withSession(['sub_user' => ['id' => 7, 'role' => 'manager']])
            ->postJson(route('update.download'))->assertForbidden();
    }

    public function test_diagnostics_never_leaks_sync_token(): void
    {
        config(['nativephp-internal.running' => true]);
        Settings::set('branch.id', 'br_TEST');
        Settings::set('branch.code', 'T');
        Settings::set('sync.server_url', 'https://server.test');
        Settings::set('sync.token', 'TOP-SECRET-DIAGNOSTICS-TOKEN');
        config(['sync.branch_id' => 'br_TEST', 'sync.token' => 'TOP-SECRET-DIAGNOSTICS-TOKEN']);
        SyncStatus::record('push', false, 0, 'token=TOP-SECRET-DIAGNOSTICS-TOKEN failed', 10);

        $this->get(route('diagnostics.page'))->assertDontSee('TOP-SECRET-DIAGNOSTICS-TOKEN');
        $this->getJson(route('diagnostics.status'))->assertDontSee('TOP-SECRET-DIAGNOSTICS-TOKEN');
    }
}
