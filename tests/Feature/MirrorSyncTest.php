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
}
