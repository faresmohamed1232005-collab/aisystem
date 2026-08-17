<?php

namespace Tests\Feature;

use App\Models\BranchModel;
use App\Models\User;
use App\Support\ActiveBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2ب (اتجاه B) — تجريدة «الفرع العامل». الديسكتوب = هوية الجهاز الثابتة؛ الموقع =
 * الفرع الذي يختاره المالك (session) وإلا المركز الافتراضي. أساس ختم وعرض البيانات لكل فرع.
 */
class ActiveBranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_desktop_ignores_session_and_uses_device_branch(): void
    {
        config(['nativephp-internal.running' => true, 'sync.branch_id' => 'br_DEVICE']);
        session([ActiveBranch::SESSION_KEY => 'br_OTHER']); // يجب تجاهله على الديسكتوب

        $this->assertSame('br_DEVICE', ActiveBranch::id());
        $this->assertFalse(ActiveBranch::isSelected());
    }

    public function test_web_defaults_to_installation_branch_when_nothing_selected(): void
    {
        config(['nativephp-internal.running' => false, 'sync.branch_id' => 'server']);

        $this->assertSame('server', ActiveBranch::id()); // سلوك اليوم — متوافق مع القديم
        $this->assertFalse(ActiveBranch::isSelected());
    }

    public function test_web_uses_selected_branch_from_session(): void
    {
        config(['nativephp-internal.running' => false, 'sync.branch_id' => 'server']);
        session([ActiveBranch::SESSION_KEY => 'br_A']);

        $this->assertSame('br_A', ActiveBranch::id());
        $this->assertTrue(ActiveBranch::isSelected());
    }

    public function test_owner_can_switch_and_clear_active_branch(): void
    {
        config(['nativephp-internal.running' => false]);
        $owner = User::factory()->create(['is_approved' => 1]);
        $branch = BranchModel::create([
            'branch_id' => 'br_' . Str::ulid(), 'code' => 'A', 'name' => 'فرع', 'type' => 'pharmacy',
            'user_id' => $owner->id, 'status' => 'active', 'registered_at' => now(),
        ]);

        $this->actingAs($owner)->post(route('branches.switch'), ['branch_id' => $branch->branch_id])
            ->assertRedirect();
        $this->assertSame($branch->branch_id, session(ActiveBranch::SESSION_KEY));

        $this->actingAs($owner)->post(route('branches.switch'), ['branch_id' => ''])->assertRedirect();
        $this->assertNull(session(ActiveBranch::SESSION_KEY));
    }

    public function test_switch_rejects_branch_of_another_owner(): void
    {
        config(['nativephp-internal.running' => false]);
        $owner = User::factory()->create(['is_approved' => 1]);
        $other = User::factory()->create(['is_approved' => 1]);
        $foreign = BranchModel::create([
            'branch_id' => 'br_' . Str::ulid(), 'code' => 'X', 'type' => 'pharmacy',
            'user_id' => $other->id, 'status' => 'active', 'registered_at' => now(),
        ]);

        $this->actingAs($owner)->post(route('branches.switch'), ['branch_id' => $foreign->branch_id])
            ->assertForbidden();
        $this->assertNull(session(ActiveBranch::SESSION_KEY));
    }
}
