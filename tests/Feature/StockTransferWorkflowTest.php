<?php

namespace Tests\Feature;

use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2ب — workflow التحويلات (طرف التطبيق، لا المزامنة): إنشاء يخصم من المصدر،
 * استلام يضيف للوجهة، رفض، وحارس الكمية. كل خطوة على هوية فرع مختلفة (config branch_id).
 *
 * SQLite :memory:. نتحكّم في Branch::id() عبر config('sync.branch_id') لأن هيدرة
 * AppServiceProvider لا تطمس القيمة عند غياب Settings (فارغة في التست).
 */
class StockTransferWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actAs(string $branchId, string $code = 'A'): User
    {
        config(['sync.branch_id' => $branchId, 'sync.branch_code' => $code]);
        $user = User::create([
            'name' => 'مالك', 'email' => 'owner'.Str::random(4).'@t.com',
            'password' => bcrypt('secret123'), 'is_approved' => 1,
        ]);
        $this->actingAs($user);
        return $user;
    }

    private function drug(string $name = 'دواء'): int
    {
        return DB::table('drugs')->insertGetId([
            'uuid' => (string) Str::ulid(), 'name_ar' => $name, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function seedInv(int $owner, string $branch, int $drug, float $qty, string $expiry = '2027-01-01', float $cost = 10): int
    {
        return DB::table('user_drug_inventory')->insertGetId([
            'uuid' => (string) Str::ulid(), 'user_id' => $owner, 'branch_id' => $branch, 'drug_id' => $drug,
            'quantity' => $qty, 'expiry_date' => $expiry, 'cost_price' => $cost,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function seedBranch(string $branchId, string $code, int $owner): void
    {
        DB::table('branches')->insert([
            'branch_id' => $branchId, 'code' => $code, 'user_id' => $owner, 'uuid' => (string) Str::ulid(),
            'status' => 'active', 'type' => 'pharmacy', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** ينشئ تحويلاً (draft) ويعتمده ويرسله، فيصير sent مع خصم المصدر. يُعيد التحويل. */
    private function createApproveSend(int $ownerId, int $invId, float $qty = 8): StockTransfer
    {
        $this->post(route('stock-transfers.store'), [
            'to_branch_id' => 'br_B',
            'items' => [['inventory_id' => $invId, 'quantity' => $qty]],
        ])->assertRedirect();

        $transfer = StockTransfer::first();
        $this->post(route('stock-transfers.approve', $transfer))->assertRedirect();
        $this->post(route('stock-transfers.send', $transfer))->assertRedirect();
        $transfer->refresh();

        return $transfer;
    }

    public function test_create_transfer_marks_draft_without_decrement(): void
    {
        $owner = $this->actAs('br_A', 'A');
        $drug  = $this->drug();
        $invId = $this->seedInv($owner->id, 'br_A', $drug, 20);
        $this->seedBranch('br_B', 'B', $owner->id);

        $this->post(route('stock-transfers.store'), [
            'to_branch_id' => 'br_B',
            'items' => [['inventory_id' => $invId, 'quantity' => 8]],
            'notes' => 'عاجل',
        ])->assertRedirect();

        $this->assertDatabaseHas('stock_transfers', [
            'user_id' => $owner->id, 'from_branch_id' => 'br_A', 'to_branch_id' => 'br_B', 'status' => 'draft',
        ]);
        $transfer = StockTransfer::first();
        $this->assertDatabaseHas('stock_transfer_items', [
            'transfer_id' => $transfer->id, 'drug_id' => $drug, 'quantity' => 8, 'cost_price' => 10,
        ]);
        // مسودة: مخزون المصدر لم يُخصم (لا يزال 20).
        $this->assertSame(20.0, (float) DB::table('user_drug_inventory')->where('id', $invId)->value('quantity'));
    }

    public function test_approve_then_send_decrements_source(): void
    {
        $owner = $this->actAs('br_A', 'A');
        $drug  = $this->drug();
        $invId = $this->seedInv($owner->id, 'br_A', $drug, 20);
        $this->seedBranch('br_B', 'B', $owner->id);

        $this->post(route('stock-transfers.store'), [
            'to_branch_id' => 'br_B',
            'items' => [['inventory_id' => $invId, 'quantity' => 8]],
        ])->assertRedirect();
        $transfer = StockTransfer::first();

        // اعتماد: approved بلا خصم.
        $this->post(route('stock-transfers.approve', $transfer))->assertRedirect();
        $transfer->refresh();
        $this->assertSame('approved', $transfer->status);
        $this->assertNotNull($transfer->approved_at);
        $this->assertSame(20.0, (float) DB::table('user_drug_inventory')->where('id', $invId)->value('quantity'));

        // إرسال: sent + خصم (20 → 12).
        $this->post(route('stock-transfers.send', $transfer))->assertRedirect();
        $transfer->refresh();
        $this->assertSame('sent', $transfer->status);
        $this->assertNotNull($transfer->sent_at);
        $this->assertSame(12.0, (float) DB::table('user_drug_inventory')->where('id', $invId)->value('quantity'));
    }

    public function test_send_rejects_quantity_over_available(): void
    {
        $owner = $this->actAs('br_A', 'A');
        $drug  = $this->drug();
        $invId = $this->seedInv($owner->id, 'br_A', $drug, 8);
        $this->seedBranch('br_B', 'B', $owner->id);

        // ننشئ مسودة بكل الكمية المتاحة ثم نعتمدها.
        $this->post(route('stock-transfers.store'), [
            'to_branch_id' => 'br_B',
            'items' => [['inventory_id' => $invId, 'quantity' => 8]],
        ])->assertRedirect();
        $transfer = StockTransfer::first();
        $this->post(route('stock-transfers.approve', $transfer))->assertRedirect();

        // المخزون قلّ (بيع مثلاً) قبل الإرسال → send يجب أن يرفض ولا يخصم.
        DB::table('user_drug_inventory')->where('id', $invId)->update(['quantity' => 3]);

        $this->post(route('stock-transfers.send', $transfer))->assertRedirect()->assertSessionHasErrors('send');

        $transfer->refresh();
        $this->assertSame('approved', $transfer->status); // لم يُرسل
        $this->assertSame(3.0, (float) DB::table('user_drug_inventory')->where('id', $invId)->value('quantity'));
    }

    public function test_store_rejects_quantity_over_available(): void
    {
        $owner = $this->actAs('br_A', 'A');
        $drug  = $this->drug();
        $invId = $this->seedInv($owner->id, 'br_A', $drug, 5);
        $this->seedBranch('br_B', 'B', $owner->id);

        $this->post(route('stock-transfers.store'), [
            'to_branch_id' => 'br_B',
            'items' => [['inventory_id' => $invId, 'quantity' => 10]],
        ])->assertRedirect()->assertSessionHasErrors('items');

        // لا تحويل أُنشئ والمخزون لم يتغيّر (المعاملة تراجعت).
        $this->assertSame(0, StockTransfer::count());
        $this->assertSame(5.0, (float) DB::table('user_drug_inventory')->where('id', $invId)->value('quantity'));
    }

    public function test_receive_increments_destination_and_marks_received(): void
    {
        // نُنشئ التحويل كمصدر br_A ونعتمده ونرسله أولاً.
        $owner = $this->actAs('br_A', 'A');
        $drug  = $this->drug();
        $invId = $this->seedInv($owner->id, 'br_A', $drug, 20);
        $this->seedBranch('br_B', 'B', $owner->id);

        $transfer = $this->createApproveSend($owner->id, $invId, 8);
        $item     = $transfer->items()->first();

        // نبدّل الهوية للوجهة br_B ونستلم.
        config(['sync.branch_id' => 'br_B', 'sync.branch_code' => 'B']);

        $this->post(route('stock-transfers.receive.confirm', $transfer), [
            'items' => [$item->id => ['received' => 8, 'damaged' => 1]],
        ])->assertRedirect();

        $transfer->refresh();
        $this->assertSame('received', $transfer->status);
        $this->assertNotNull($transfer->received_at);

        // أُضيف لمخزون الوجهة br_B (نفس الدواء+الصلاحية) = 8، وبفرع مختلف عن المصدر.
        $destInv = DB::table('user_drug_inventory')
            ->where('user_id', $owner->id)->where('branch_id', 'br_B')->where('drug_id', $drug)->first();
        $this->assertNotNull($destInv);
        $this->assertSame(8.0, (float) $destInv->quantity);

        // مخزون المصدر ما زال منقوصاً (12) — لم يتضاعف.
        $this->assertSame(12.0, (float) DB::table('user_drug_inventory')->where('id', $invId)->value('quantity'));

        $item->refresh();
        $this->assertSame(8.0, (float) $item->received_quantity);
        $this->assertSame(1.0, (float) $item->damaged_quantity);
    }

    public function test_reject_marks_transfer_rejected(): void
    {
        $owner = $this->actAs('br_A', 'A');
        $drug  = $this->drug();
        $invId = $this->seedInv($owner->id, 'br_A', $drug, 20);
        $this->seedBranch('br_B', 'B', $owner->id);

        $transfer = $this->createApproveSend($owner->id, $invId, 8);

        config(['sync.branch_id' => 'br_B', 'sync.branch_code' => 'B']);
        $this->post(route('stock-transfers.reject', $transfer), [
            'reject_reason' => 'كمية غير مطابقة',
        ])->assertRedirect();

        $transfer->refresh();
        $this->assertSame('rejected', $transfer->status);
        $this->assertSame('كمية غير مطابقة', $transfer->reject_reason);
    }

    public function test_all_phase2b_pages_render(): void
    {
        $owner = $this->actAs('br_A', 'A');
        $drug  = $this->drug();
        $invId = $this->seedInv($owner->id, 'br_A', $drug, 20);
        $this->seedBranch('br_A', 'A', $owner->id);
        $this->seedBranch('br_B', 'B', $owner->id);

        $branch = \App\Models\BranchModel::where('branch_id', 'br_A')->first();
        // تعديل الفرع بقى في modal داخل index — نتأكد إن الـ update url و data-* موجودين.
        $this->get(route('branches.index'))->assertOk()->assertSee('edit-modal', false);
        $this->get(route('branches.show', $branch))->assertOk();

        $this->get(route('stock-transfers.index'))->assertOk();
        $this->get(route('stock-transfers.index', ['tab' => 'incoming']))->assertOk();
        $this->get(route('stock-transfers.create'))->assertOk()->assertSee('br_B', false);
        $this->get(route('stock-transfers.drug-batches', ['drug_id' => $drug]))->assertOk()->assertJsonCount(1);

        // أنشئ تحويلاً واعتمده وأرسله ثم اعرض شاشتَي التفصيل والاستلام (كوجهة).
        $transfer = $this->createApproveSend($owner->id, $invId, 8);

        $this->get(route('stock-transfers.show', $transfer))->assertOk()->assertSee($transfer->transfer_number);

        config(['sync.branch_id' => 'br_B', 'sync.branch_code' => 'B']);
        $this->get(route('stock-transfers.receive', $transfer))->assertOk();
    }

    public function test_branch_update_saves_metadata_and_flags(): void
    {
        $owner  = $this->actAs('br_A', 'A');
        $this->seedBranch('br_A', 'A', $owner->id);
        $branch = \App\Models\BranchModel::where('branch_id', 'br_A')->first();

        $this->put(route('branches.update', $branch), [
            'name' => 'فرع المعادي', 'type' => 'warehouse', 'governorate' => 'القاهرة',
            'status' => 'stopped', 'allow_transfer_out' => 1, 'allow_transfer_in' => 0,
        ])->assertRedirect();

        $branch->refresh();
        $this->assertSame('فرع المعادي', $branch->name);
        $this->assertSame('warehouse', $branch->type);
        $this->assertSame('stopped', $branch->status);
        $this->assertTrue((bool) $branch->allow_transfer_out);
        $this->assertFalse((bool) $branch->allow_transfer_in);
    }
}
