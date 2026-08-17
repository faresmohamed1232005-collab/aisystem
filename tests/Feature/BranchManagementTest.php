<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 2ب (اتجاه B) — إدارة الفروع مركزياً على السيرفر: المالك ينشئ فرعاً بكود قبل
 * تثبيت أي جهاز، فيمكن ضبط مخزونه وفواتيره ثم توصيل أجهزته بالكود لاحقاً.
 */
class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create(['is_approved' => 1]);
    }

    public function test_create_page_renders(): void
    {
        $this->actingAs($this->owner())->get(route('branches.create'))
            ->assertOk()
            ->assertSee('إنشاء فرع جديد');
    }

    public function test_owner_can_create_a_branch_on_the_server(): void
    {
        $owner = $this->owner();

        $res = $this->actingAs($owner)->post(route('branches.store'), [
            'code' => 'a',
            'name' => 'فرع المعادي',
            'type' => 'pharmacy',
        ]);

        $res->assertRedirect();
        $branch = DB::table('branches')->where('code', 'A')->first();
        $this->assertNotNull($branch);
        $this->assertSame((int) $owner->id, (int) $branch->user_id);
        $this->assertStringStartsWith('br_', $branch->branch_id);
        $this->assertNotNull($branch->uuid);              // Syncable ختم uuid → يُسحب للأجهزة
        $this->assertSame('active', $branch->status);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post(route('branches.store'), ['code' => 'A', 'type' => 'pharmacy'])->assertRedirect();

        // نفس الكود مرة أخرى (حتى لنفس المالك) — مرفوض لأن الكود فريد عالمياً.
        $this->actingAs($owner)->post(route('branches.store'), ['code' => 'A', 'type' => 'pharmacy'])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, DB::table('branches')->where('code', 'A')->count());
    }
}
