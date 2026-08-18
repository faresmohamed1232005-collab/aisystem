<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Native\Desktop\Contracts\WindowManager;
use Tests\TestCase;

/**
 * تعدّد النوافذ على الديسكتوب: فتح شاشة مسموح بها في نافذة مستقلة بـ id فريد، مع حراسة
 * الديسكتوب/الدخول والقائمة البيضاء. (ظهور نافذة OS فعلية وبقاء الفاتورة يُتحقّق في بناء حقيقي.)
 */
class DesktopWindowTest extends TestCase
{
    use RefreshDatabase;

    private function desktop(): void
    {
        config(['nativephp-internal.running' => true, 'sync.branch_id' => 'br_TEST']);
        Settings::set('branch.id', 'br_TEST');
        Settings::set('branch.code', 'T');
        Settings::set('sync.server_url', 'https://server.test');
        Settings::set('sync.token', 'test-token');
        Settings::set('initial_setup_completed_at', now()->toDateTimeString());
    }

    private function owner(): User
    {
        return User::factory()->create(['is_approved' => 1]);
    }

    public function test_desktop_opens_new_window_with_unique_id_and_target_route(): void
    {
        $this->desktop();

        // نستبدل مدير النوافذ بمُسجِّل خفيف يلتقط الـ id والمسار بلا فتح نافذة فعلية أو عميل.
        $window = new class {
            public ?string $routeName = null;
            public function route(string $name, array $params = []): self { $this->routeName = $name; return $this; }
            public function __call($m, $a): self { return $this; } // width/height/minWidth/minHeight/maximized
        };
        $manager = new class($window) implements WindowManager {
            public array $opened = [];
            public function __construct(private object $window) {}
            public function open(string $id = 'main') { $this->opened[] = $id; return $this->window; }
            public function close($id = null) {}
            public function hide($id = null) {}
            public function current(): \Native\Desktop\Windows\Window { throw new \RuntimeException('n/a'); }
            public function all(): array { return []; }
            public function get(string $id): \Native\Desktop\Windows\Window { throw new \RuntimeException('n/a'); }
            public function reload($id = null): void {}
        };
        $this->app->instance(WindowManager::class, $manager);

        $this->actingAs($this->owner())
            ->postJson(route('desktop.window.open'), ['target' => 'products.index'])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertCount(1, $manager->opened);
        $this->assertStringStartsWith('win-', $manager->opened[0]);   // id فريد لنافذة مستقلة
        $this->assertSame('products.index', $window->routeName);      // الشاشة الصحيحة
    }

    public function test_target_not_in_allow_list_is_rejected(): void
    {
        $this->desktop();
        Http::fake();
        $owner = $this->owner();

        // logout مسار حقيقي لكنه خارج القائمة البيضاء (مدمّر) → مرفوض.
        $this->actingAs($owner)->postJson(route('desktop.window.open'), ['target' => 'logout'])->assertStatus(422);
        // URL خارجي → مرفوض.
        $this->actingAs($owner)->postJson(route('desktop.window.open'), ['target' => 'https://evil.test'])->assertStatus(422);
        // بدون هدف → مرفوض.
        $this->actingAs($owner)->postJson(route('desktop.window.open'), [])->assertStatus(422);

        Http::assertNothingSent(); // لم تُفتح أي نافذة
    }

    public function test_web_returns_not_found(): void
    {
        config(['nativephp-internal.running' => false]);
        Http::fake();

        $this->actingAs($this->owner())
            ->postJson(route('desktop.window.open'), ['target' => 'products.index'])
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_guest_cannot_open_a_window(): void
    {
        $this->desktop();
        Http::fake();

        $this->postJson(route('desktop.window.open'), ['target' => 'products.index'])->assertUnauthorized();
        Http::assertNothingSent();
    }
}
