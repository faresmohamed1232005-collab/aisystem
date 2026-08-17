<?php

namespace Tests\Feature;

use App\Models\BranchModel;
use App\Models\User;
use App\Models\UserDrugInventory;
use App\Services\Sync\SyncPullQuery;
use App\Support\ActiveBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2ب (اتجاه B) — Stage 3: ضبط مخزون فرع على الموقع.
 *
 * عند العمل على فرع A على الموقع، المخزون المُنشأ يُوسَم بـ branch_id=A، فيسحبه ديسكتوب A
 * عبر استراتيجية own_branch — ولا يسحبه فرع آخر. هذه هي الحلقة التي تجعل الديسكتوب يبدأ
 * ممتلئاً بمخزون فرعه المُجهَّز مركزياً.
 */
class WebOpeningStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_inventory_is_stamped_with_the_active_branch_and_pulled_by_that_branch_only(): void
    {
        config(['nativephp-internal.running' => false, 'sync.branch_id' => 'server']);

        $owner = User::factory()->create(['is_approved' => 1]);
        $branchA = BranchModel::create([
            'branch_id' => 'br_A', 'code' => 'A', 'name' => 'فرع أ', 'type' => 'pharmacy',
            'user_id' => $owner->id, 'status' => 'active', 'registered_at' => now(),
        ]);
        $drugId = DB::table('drugs')->insertGetId([
            'uuid' => (string) Str::ulid(), 'name_ar' => 'دواء', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // المالك يختار الفرع A كفرع نشط ثم يُدخل مخزوناً افتتاحياً (بدون تمرير branch_id).
        session([ActiveBranch::SESSION_KEY => $branchA->branch_id]);
        $this->actingAs($owner);

        $inv = UserDrugInventory::create([
            'user_id' => $owner->id, 'drug_id' => $drugId, 'quantity' => 40, 'expiry_date' => '2027-06-01',
        ]);

        // وُسِم بفرع A لا بالمركز 'server'، وأخذ uuid.
        $this->assertSame('br_A', $inv->branch_id);
        $this->assertNotNull($inv->uuid);

        // سحب ديسكتوب A (own_branch) يرى المخزون؛ فرع آخر لا.
        $pull = app(SyncPullQuery::class);
        $this->assertSame(1, $pull->scoped('user_drug_inventory', 'br_A', $owner->id)->count());
        $this->assertSame(0, $pull->scoped('user_drug_inventory', 'br_B', $owner->id)->count());
    }

    public function test_web_without_active_branch_stamps_the_central_default(): void
    {
        config(['nativephp-internal.running' => false, 'sync.branch_id' => 'server']);
        $owner = User::factory()->create(['is_approved' => 1]);
        $drugId = DB::table('drugs')->insertGetId([
            'uuid' => (string) Str::ulid(), 'name_ar' => 'دواء', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($owner); // بدون اختيار فرع

        $inv = UserDrugInventory::create([
            'user_id' => $owner->id, 'drug_id' => $drugId, 'quantity' => 5,
        ]);

        $this->assertSame('server', $inv->branch_id); // المركز الافتراضي — سلوك اليوم
    }
}
