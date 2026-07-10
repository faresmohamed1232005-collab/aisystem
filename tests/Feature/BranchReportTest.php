<?php

namespace Tests\Feature;

use App\Support\Roles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 4 — الداشبورد المركزية المقارنة cross-branch.
 *
 * تتأكد إن التقرير يجمّع بيانات كل الفروع (بُعد branch_id) بدل single-tenant: مبيعات/ربح
 * لكل فرع، مخزون لكل فرع، تنبيه تحويل عالق، وحارس صلاحية view_reports.
 */
class BranchReportTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create([
            'name' => 'مالك', 'email' => 'owner@t.com', 'password' => bcrypt('secret123'), 'is_approved' => 1,
        ]);
    }

    private function seedBranch(string $branchId, string $code, int $owner, string $name): void
    {
        DB::table('branches')->insert([
            'branch_id' => $branchId, 'code' => $code, 'user_id' => $owner, 'name' => $name,
            'uuid' => (string) Str::ulid(), 'status' => 'active', 'type' => 'pharmacy',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function drug(string $name = 'دواء'): int
    {
        return DB::table('drugs')->insertGetId([
            'uuid' => (string) Str::ulid(), 'name_ar' => $name, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** فاتورة بيع بربح معلوم: total إجمالي، وبند واحد بـ (price-cost)*qty. */
    private function seedSale(int $owner, string $branch, int $drug, float $total, float $price, float $cost, int $qty): void
    {
        $saleId = DB::table('sales')->insertGetId([
            'uuid' => (string) Str::ulid(), 'user_id' => $owner, 'branch_id' => $branch,
            'invoice_number' => 'INV-' . Str::random(8), 'total' => $total, 'discount' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('sale_items')->insert([
            'sale_id' => $saleId, 'drug_id' => $drug, 'quantity' => $qty,
            'price' => $price, 'cost_price' => $cost, 'subtotal' => $price * $qty,
        ]);
    }

    private function seedInv(int $owner, string $branch, int $drug, float $qty, int $min = 5, float $cost = 10): void
    {
        DB::table('user_drug_inventory')->insert([
            'uuid' => (string) Str::ulid(), 'user_id' => $owner, 'branch_id' => $branch, 'drug_id' => $drug,
            'quantity' => $qty, 'min_quantity' => $min, 'cost_price' => $cost, 'expiry_date' => '2027-01-01',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_dashboard_aggregates_both_branches(): void
    {
        $owner = $this->owner();
        $this->seedBranch('br_A', 'A', $owner->id, 'فرع المعادي');
        $this->seedBranch('br_B', 'B', $owner->id, 'فرع مدينة نصر');
        $drug = $this->drug();

        // فرع A: بيع 1000 (ربح 400)، فرع B: بيع 600 (ربح 200).
        $this->seedSale($owner->id, 'br_A', $drug, 1000, 100, 60, 10); // ربح 40*10=400
        $this->seedSale($owner->id, 'br_B', $drug, 600, 100, 60, 6);   // ربح 40*6=240

        $res = $this->actingAs($owner)->get(route('reports.branches', ['period' => 'today']));
        $res->assertOk();
        $res->assertSee('فرع المعادي');
        $res->assertSee('فرع مدينة نصر');

        $branches = collect($res->viewData('branches'));
        $this->assertCount(2, $branches);

        $a = $branches->firstWhere('branch_id', 'br_A');
        $b = $branches->firstWhere('branch_id', 'br_B');
        $this->assertSame(1000.0, $a['revenue']);
        $this->assertSame(400.0, $a['profit']);
        $this->assertSame(600.0, $b['revenue']);
        $this->assertSame(240.0, $b['profit']);

        // الإجماليات = مجموع الفرعين.
        $totals = $res->viewData('totals');
        $this->assertSame(1600.0, $totals['revenue']);
        $this->assertSame(640.0, $totals['profit']);
        $this->assertSame(2, $totals['invoices']);
    }

    public function test_inventory_and_low_stock_per_branch(): void
    {
        $owner = $this->owner();
        $this->seedBranch('br_A', 'A', $owner->id, 'فرع A');
        $this->seedBranch('br_B', 'B', $owner->id, 'فرع B');
        $drug = $this->drug();

        $this->seedInv($owner->id, 'br_A', $drug, 100, 5);  // كافٍ
        $this->seedInv($owner->id, 'br_B', $drug, 3, 5);    // تحت الحد (3 <= 5)

        $res = $this->actingAs($owner)->get(route('reports.branches'));
        $res->assertOk();

        $branches = collect($res->viewData('branches'));
        $this->assertSame(0, $branches->firstWhere('branch_id', 'br_A')['low_stock']);
        $this->assertSame(1, $branches->firstWhere('branch_id', 'br_B')['low_stock']);
        $this->assertSame(100.0, $branches->firstWhere('branch_id', 'br_A')['inv_units']);
    }

    public function test_stuck_transfer_alert_appears(): void
    {
        $owner = $this->owner();
        $this->seedBranch('br_A', 'A', $owner->id, 'فرع A');
        $this->seedBranch('br_B', 'B', $owner->id, 'فرع B');

        DB::table('stock_transfers')->insert([
            'uuid' => (string) Str::ulid(), 'user_id' => $owner->id,
            'from_branch_id' => 'br_A', 'to_branch_id' => 'br_B', 'transfer_number' => 'TR-A-0001',
            'status' => 'sent', 'sent_at' => now()->subHours(60), 'branch_id' => 'br_A',
            'created_at' => now()->subHours(60), 'updated_at' => now()->subHours(60),
        ]);

        $res = $this->actingAs($owner)->get(route('reports.branches'));
        $res->assertOk();

        $alerts = collect($res->viewData('alerts'));
        $stuck = $alerts->firstWhere('type', 'transfer_stuck');
        $this->assertNotNull($stuck);
        $this->assertStringContainsString('TR-A-0001', $stuck['message']);
    }

    public function test_requires_view_reports_permission(): void
    {
        $owner = $this->owner();

        // صيدلي بلا view_reports → ممنوع.
        $this->actingAs($owner)->withSession(['sub_user' => [
            'id' => 99, 'name' => 'موظف', 'email' => 's@t.com', 'role' => Roles::PHARMACIST,
        ]])->get(route('reports.branches'))->assertForbidden();

        // محاسب عنده view_reports → مسموح.
        $this->actingAs($owner)->withSession(['sub_user' => [
            'id' => 98, 'name' => 'محاسب', 'email' => 'a@t.com', 'role' => Roles::ACCOUNTANT,
        ]])->get(route('reports.branches'))->assertOk();
    }
}
