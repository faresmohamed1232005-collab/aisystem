<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesktopEntryPointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'nativephp-internal.running' => false,
            'sync.branch_id' => null,
            'sync.server_branch_id' => 'server',
        ]);
        Settings::flush();
    }

    public function test_web_guest_sees_landing_page(): void
    {
        $this->get('/')->assertOk()->assertViewIs('landing');
    }

    public function test_unregistered_desktop_is_sent_to_setup(): void
    {
        config(['nativephp-internal.running' => true]);

        $this->get('/')->assertRedirect('/setup');
    }

    public function test_registered_desktop_guest_is_sent_to_login(): void
    {
        $this->registerDesktopBranch();

        $this->get('/')->assertRedirectToRoute('login');
    }

    public function test_registered_desktop_user_is_sent_to_dashboard(): void
    {
        $this->registerDesktopBranch();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirectToRoute('dashboard');
    }

    public function test_desktop_cannot_open_self_registration(): void
    {
        $this->registerDesktopBranch();

        $this->get('/register')->assertRedirectToRoute('login');
        $this->post('/register')->assertRedirectToRoute('login');
    }

    public function test_registered_desktop_without_completed_sync_is_held_at_setup_sync(): void
    {
        // مسجّل لكن أول مزامنة لم تكتمل (لا علَم) → بوابة Part A تحوّشه لشاشة التنزيل،
        // فلا يدخل التطبيق بقاعدة نصف محمّلة.
        $this->registerDesktopBranch(completed: false);

        $this->get('/')->assertRedirectToRoute('setup.sync.show');
        $this->get('/login')->assertRedirectToRoute('setup.sync.show');
    }

    private function registerDesktopBranch(bool $completed = true): void
    {
        config(['nativephp-internal.running' => true]);
        Settings::set('branch.id', 'br_TEST');
        Settings::set('branch.code', 'T');
        Settings::set('sync.server_url', 'https://server.test');
        Settings::set('sync.token', 'test-token');
        config(['sync.branch_id' => 'br_TEST']);

        // ديسكتوب مكتمل الإعداد (المسار الطبيعي بعد أول مزامنة). مرّر completed:false
        // لاختبار حالة ما-قبل-الاكتمال حيث تحوّش البوابة المستخدم لشاشة التنزيل.
        if ($completed) {
            Settings::set('initial_setup_completed_at', now()->toDateTimeString());
        }
    }
}
