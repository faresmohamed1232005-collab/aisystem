<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2ب — الديسكتوب كنسخة كاملة من الموقع (full mirror).
 *
 * يتحقّق أن بيانات المالك التشغيلية تُسحب server→branch مقيّدةً بالمالك (owner-scoped)،
 * وأن الأبناء بلا user_id تُقيَّد بمالك أبيها (parent-scoped)، وأن ترجمة FK (id ⇄ uuid)
 * تحفظ سلامة العلاقات في الاتجاهين. قاعدة الفرع = SQLite (:memory:) كبقية اختبارات المزامنة.
 */
class MirrorSyncTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-sync-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['sync.token' => $this->token, 'sync.server_url' => 'https://server.test']);
    }

    private function headers(): array
    {
        return ['X-Sync-Token' => $this->token, 'Accept' => 'application/json'];
    }

    private function makeOwner(array $o = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name' => 'صيدلية', 'email' => 'owner@test.com', 'password' => Hash::make('secret123'),
            'is_approved' => 1, 'uuid' => (string) Str::ulid(), 'created_at' => now(), 'updated_at' => now(),
        ], $o));
    }

    private function makeBranch(string $branchId, string $code, int $ownerId): void
    {
        DB::table('branches')->insert([
            'branch_id' => $branchId, 'code' => $code, 'user_id' => $ownerId, 'uuid' => (string) Str::ulid(),
            'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function pull(string $branchId, array $cursors = [])
    {
        return $this->withHeaders($this->headers())->postJson('/api/sync/pull', [
            'branch_id' => $branchId, 'cursors' => $cursors,
        ]);
    }

    /** المراجع (customers/products/suppliers...) تُسحب لمالك الفرع فقط. */
    public function test_owner_reference_data_is_pulled_scoped_to_the_branch_owner(): void
    {
        $owner = $this->makeOwner();
        $other = $this->makeOwner(['email' => 'other@test.com', 'uuid' => (string) Str::ulid()]);
        $this->makeBranch('br_A', 'A', $owner);
        $now = now()->toDateTimeString();

        DB::table('customers')->insert([
            'uuid' => (string) Str::ulid(), 'user_id' => $owner, 'code' => 'C-MINE', 'name' => 'عميلي',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('customers')->insert([
            'uuid' => (string) Str::ulid(), 'user_id' => $other, 'code' => 'C-OTHER', 'name' => 'عميل غيري',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('suppliers')->insert([
            'uuid' => (string) Str::ulid(), 'user_id' => $owner, 'code' => 'S-1', 'name' => 'موردي',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $res = $this->pull('br_A')->assertOk();

        $customerCodes = array_column($res->json('tables.customers.rows'), 'code');
        $this->assertSame(['C-MINE'], $customerCodes); // عميل المالك الآخر لا يظهر
        $this->assertCount(1, $res->json('tables.suppliers.rows'));
    }

    /** employee_transactions يترجم employee_id/expense_id إلى uuid عند السحب. */
    public function test_employee_transaction_fk_is_translated_to_uuid_on_pull(): void
    {
        $owner = $this->makeOwner();
        $this->makeBranch('br_A', 'A', $owner);
        $now = now()->toDateTimeString();

        $empUuid = (string) Str::ulid();
        $empId = DB::table('employees')->insertGetId([
            'uuid' => $empUuid, 'user_id' => $owner, 'name' => 'موظف', 'base_salary' => 1000,
            'hired_at' => now()->toDateString(), 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('employee_transactions')->insert([
            'uuid' => (string) Str::ulid(), 'user_id' => $owner, 'employee_id' => $empId,
            'type' => 'salary', 'amount' => 500, 'month' => 8, 'year' => 2026,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $rows = $this->pull('br_A')->assertOk()->json('tables.employee_transactions.rows');
        $this->assertCount(1, $rows);
        $this->assertSame($empUuid, $rows[0]['employee_id_uuid']); // FK متحوّل لـ uuid
        $this->assertArrayNotHasKey('employee_id', $rows[0]);      // id المحلي لا يُبَثّ
    }

    private function makeDrug(string $name = 'دواء'): string
    {
        $uuid = (string) Str::ulid();
        DB::table('drugs')->insert(['uuid' => $uuid, 'name_ar' => $name, 'created_at' => now(), 'updated_at' => now()]);
        return $uuid;
    }

    private function makeSale(int $owner, string $invoice): array
    {
        $uuid = (string) Str::ulid();
        $id = DB::table('sales')->insertGetId([
            'uuid' => $uuid, 'user_id' => $owner, 'invoice_number' => $invoice,
            'total' => 100, 'paid' => 100, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$id, $uuid];
    }

    /** المبيعات owner-scoped، وبنودها (بلا user_id) parent-scoped: بند البيع لمالك آخر لا يُسحب. */
    public function test_sale_items_are_parent_scoped_and_fk_translated_on_pull(): void
    {
        $owner = $this->makeOwner();
        $other = $this->makeOwner(['email' => 'other@test.com', 'uuid' => (string) Str::ulid()]);
        $this->makeBranch('br_A', 'A', $owner);
        $drug = $this->makeDrug();
        $drugId = DB::table('drugs')->where('uuid', $drug)->value('id');
        $now = now()->toDateTimeString();

        [$mineId, $mineUuid] = $this->makeSale($owner, 'INV-MINE');
        [$otherId] = $this->makeSale($other, 'INV-OTHER');

        $mineItemUuid = (string) Str::ulid();
        DB::table('sale_items')->insert([
            'uuid' => $mineItemUuid, 'sale_id' => $mineId, 'drug_id' => $drugId,
            'quantity' => 2, 'price' => 50, 'subtotal' => 100, 'created_at' => $now, 'updated_at' => $now,
        ]);
        // بند بيع مالك آخر — يجب ألا يُسحب لفرع owner.
        DB::table('sale_items')->insert([
            'uuid' => (string) Str::ulid(), 'sale_id' => $otherId, 'drug_id' => $drugId,
            'quantity' => 9, 'price' => 9, 'subtotal' => 81, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $res = $this->pull('br_A')->assertOk();

        // المبيعات: مبيعة المالك فقط.
        $this->assertSame(['INV-MINE'], array_column($res->json('tables.sales.rows'), 'invoice_number'));
        // بنود المبيعات: بند المالك فقط (parent-scoped).
        $items = $res->json('tables.sale_items.rows');
        $this->assertCount(1, $items);
        $this->assertSame($mineItemUuid, $items[0]['uuid']);
        $this->assertSame($mineUuid, $items[0]['sale_id_uuid']); // FK الأب متحوّل لـ uuid
        $this->assertSame($drug, $items[0]['drug_id_uuid']);
        $this->assertArrayNotHasKey('sale_id', $items[0]);
    }

    /** عند التطبيق على الفرع: بند البيع يربط بأبيه المحلي عبر sale_id_uuid → sales.id المحلي. */
    public function test_pulled_sale_item_resolves_to_local_parent_on_apply(): void
    {
        $owner = $this->makeOwner();
        $drug = $this->makeDrug();
        $saleUuid = (string) Str::ulid();
        $itemUuid = (string) Str::ulid();
        $now = now()->toDateTimeString();

        $repo = app(\App\Services\Sync\SyncRepository::class);
        // الأب أولاً (زي ترتيب pull).
        $repo->applyBatch('sales', [[
            'uuid' => $saleUuid, 'user_id' => $owner, 'invoice_number' => 'INV-APPLY',
            'total' => 100, 'paid' => 100, 'created_at' => $now, 'updated_at' => $now,
        ]], true);
        $repo->applyBatch('sale_items', [[
            'uuid' => $itemUuid, 'sale_id_uuid' => $saleUuid, 'drug_id_uuid' => $drug,
            'quantity' => 3, 'price' => 10, 'subtotal' => 30, 'created_at' => $now, 'updated_at' => $now,
        ]], true);

        $localSaleId = DB::table('sales')->where('uuid', $saleUuid)->value('id');
        $item = DB::table('sale_items')->where('uuid', $itemUuid)->first();
        $this->assertNotNull($item);
        $this->assertSame((int) $localSaleId, (int) $item->sale_id); // ربط بالأب المحلي الصحيح
        $this->assertSame((int) DB::table('drugs')->where('uuid', $drug)->value('id'), (int) $item->drug_id);
        // مُعلَّم كمتزامن حتى لا يُعاد رفعه (منع echo).
        $this->assertSame($item->updated_at, $item->synced_at);
    }
}
