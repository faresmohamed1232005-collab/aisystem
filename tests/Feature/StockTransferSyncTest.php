<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2أ — الأساس المعماري: branch_id في المخزون + المزامنة ثنائية الاتجاه للتحويلات
 * + لقطات المخزون + حراسة server-master + ثبات updated_at (منع echo-loop).
 *
 * SQLite :memory: (نوع قاعدة الفرع). يمتد على نمط SyncTest.
 */
class StockTransferSyncTest extends TestCase
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

    private function makeDrug(string $name = 'دواء'): string
    {
        $uuid = (string) Str::ulid();
        DB::table('drugs')->insert(['uuid' => $uuid, 'name_ar' => $name, 'created_at' => now(), 'updated_at' => now()]);
        return $uuid;
    }

    private function makeBranch(string $branchId, string $code, int $ownerId): void
    {
        DB::table('branches')->insert([
            'branch_id' => $branchId, 'code' => $code, 'user_id' => $ownerId, 'uuid' => (string) Str::ulid(),
            'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function push(string $branchId, array $tables)
    {
        return $this->withHeaders($this->headers())->postJson('/api/sync/push', [
            'branch_id' => $branchId, 'tables' => $tables,
        ]);
    }

    private function pull(string $branchId, array $cursors = [])
    {
        return $this->withHeaders($this->headers())->postJson('/api/sync/pull', [
            'branch_id' => $branchId, 'cursors' => $cursors,
        ]);
    }

    // ─────────── D1: مخزون فرعين لا يتصادم على السيرفر ───────────

    public function test_two_branches_inventory_do_not_collide_on_server(): void
    {
        $ownerId = $this->makeOwner();
        $drug    = $this->makeDrug();
        $now     = now()->toDateTimeString();

        // فرع A: 10 علب من الدواء (صلاحية 2027-01-01)
        $this->push('br_A', ['user_drug_inventory' => [[
            'uuid' => (string) Str::ulid(), 'user_id' => $ownerId, 'branch_id' => 'br_A',
            'drug_id_uuid' => $drug, 'quantity' => 10, 'expiry_date' => '2027-01-01',
            'created_at' => $now, 'updated_at' => $now,
        ]]])->assertOk();

        // فرع B: 5 علب من نفس الدواء + نفس الصلاحية — نفس user_id لكن branch مختلف.
        $this->push('br_B', ['user_drug_inventory' => [[
            'uuid' => (string) Str::ulid(), 'user_id' => $ownerId, 'branch_id' => 'br_B',
            'drug_id_uuid' => $drug, 'quantity' => 5, 'expiry_date' => '2027-01-01',
            'created_at' => $now, 'updated_at' => $now,
        ]]])->assertOk();

        // بدون branch_id في المفتاح كان الـ push الثاني يكسر بـ unique violation.
        $drugId = DB::table('drugs')->where('uuid', $drug)->value('id');
        $this->assertSame(2, DB::table('user_drug_inventory')->where('drug_id', $drugId)->count());
        $this->assertSame(10.0, (float) DB::table('user_drug_inventory')->where('branch_id', 'br_A')->value('quantity'));
        $this->assertSame(5.0, (float) DB::table('user_drug_inventory')->where('branch_id', 'br_B')->value('quantity'));
    }

    // ─────────── حراسة السيرفر master: push للجداول pull-only يُتجاهل ───────────

    public function test_push_ignores_server_master_tables(): void
    {
        $ownerId = $this->makeOwner();
        $this->makeBranch('br_A', 'A', $ownerId);

        $res = $this->push('br_A', [
            // محاولة كتابة سجل فرع (server-master) — يجب أن تُتجاهل.
            'branches' => [[
                'uuid' => (string) Str::ulid(), 'branch_id' => 'br_HACK', 'code' => 'ZZ',
                'user_id' => $ownerId, 'name' => 'مخترق', 'updated_at' => now()->toDateTimeString(),
            ]],
            // محاولة كتابة لقطة مخزون (server-maintained) — يجب أن تُتجاهل.
            'branch_inventory_snapshots' => [[
                'uuid' => (string) Str::ulid(), 'user_id' => $ownerId, 'snapshot_branch_id' => 'br_A',
                'drug_id_uuid' => $this->makeDrug(), 'quantity' => 999, 'updated_at' => now()->toDateTimeString(),
            ]],
        ])->assertOk();

        $this->assertDatabaseMissing('branches', ['branch_id' => 'br_HACK']);
        $this->assertSame(0, DB::table('branch_inventory_snapshots')->count());
        $this->assertArrayNotHasKey('branches', $res->json('detail') ?? []);
        $this->assertArrayNotHasKey('branch_inventory_snapshots', $res->json('detail') ?? []);
    }

    // ─────────── ثبات updated_at عند push-apply (منع echo-loop) ───────────

    public function test_server_does_not_rewrite_updated_at_on_apply(): void
    {
        $ownerId = $this->makeOwner();
        $sentTs  = '2026-07-01 08:00:00';

        $this->push('br_A', ['customers' => [[
            'uuid' => (string) Str::ulid(), 'user_id' => $ownerId, 'code' => 'C-1', 'name' => 'عميل',
            'created_at' => $sentTs, 'updated_at' => $sentTs,
        ]]])->assertOk();

        // السيرفر يخزّن updated_at كما أُرسل (لا يستبدله بساعته) — شرط أساسي لمنع echo.
        $this->assertSame($sentTs, DB::table('customers')->where('code', 'C-1')->value('updated_at'));
    }

    // ─────────── التحويلات: round-trip + branch_party + via_parent ───────────

    public function test_transfer_pushed_by_source_is_pulled_only_by_involved_branches(): void
    {
        $ownerId = $this->makeOwner();
        $this->makeBranch('br_A', 'A', $ownerId);
        $this->makeBranch('br_B', 'B', $ownerId);
        $this->makeBranch('br_C', 'C', $ownerId);
        $drug = $this->makeDrug();
        $now  = now()->toDateTimeString();

        $transferUuid = (string) Str::ulid();
        $itemUuid     = (string) Str::ulid();

        // فرع A ينشئ تحويلاً إلى B (bidirectional push).
        $this->push('br_A', [
            'stock_transfers' => [[
                'uuid' => $transferUuid, 'user_id' => $ownerId, 'branch_id' => 'br_A',
                'from_branch_id' => 'br_A', 'to_branch_id' => 'br_B', 'transfer_number' => 'TR-A-0001',
                'status' => 'sent', 'created_at' => $now, 'updated_at' => $now,
            ]],
            'stock_transfer_items' => [[
                'uuid' => $itemUuid, 'user_id' => $ownerId, 'branch_id' => 'br_A',
                'transfer_id_uuid' => $transferUuid, 'drug_id_uuid' => $drug, 'quantity' => 5,
                'created_at' => $now, 'updated_at' => $now,
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('stock_transfers', ['uuid' => $transferUuid, 'to_branch_id' => 'br_B']);
        $this->assertDatabaseHas('stock_transfer_items', ['uuid' => $itemUuid]);

        // الوجهة B تسحب التحويل (branch_party) وبنده (via_parent).
        $bPull = $this->pull('br_B')->assertOk();
        $this->assertCount(1, $bPull->json('tables.stock_transfers.rows'));
        $this->assertSame($transferUuid, $bPull->json('tables.stock_transfers.rows.0.uuid'));
        $this->assertCount(1, $bPull->json('tables.stock_transfer_items.rows'));
        // بند التحويل يترجم transfer_id إلى transfer_id_uuid.
        $this->assertSame($transferUuid, $bPull->json('tables.stock_transfer_items.rows.0.transfer_id_uuid'));

        // فرع C غير معنيّ — لا يرى التحويل ولا بنده.
        $cPull = $this->pull('br_C')->assertOk();
        $this->assertCount(0, $cPull->json('tables.stock_transfers.rows'));
        $this->assertCount(0, $cPull->json('tables.stock_transfer_items.rows'));
    }

    // ─────────── لقطات المخزون: تُبنى على السيرفر وتُسحب للفروع الأخرى (تستثني النفس) ───────────

    public function test_inventory_push_builds_snapshot_visible_to_other_branches_only(): void
    {
        $ownerId = $this->makeOwner();
        $this->makeBranch('br_A', 'A', $ownerId);
        $this->makeBranch('br_B', 'B', $ownerId);
        $drug = $this->makeDrug();
        $now  = now()->toDateTimeString();

        // فرع A يدفع مخزونه → السيرفر يبني لقطة لـ br_A.
        $this->push('br_A', ['user_drug_inventory' => [[
            'uuid' => (string) Str::ulid(), 'user_id' => $ownerId, 'branch_id' => 'br_A',
            'drug_id_uuid' => $drug, 'quantity' => 12, 'expiry_date' => '2027-05-01',
            'created_at' => $now, 'updated_at' => $now,
        ]]])->assertOk();

        $drugId = DB::table('drugs')->where('uuid', $drug)->value('id');
        $this->assertDatabaseHas('branch_inventory_snapshots', [
            'user_id' => $ownerId, 'snapshot_branch_id' => 'br_A', 'drug_id' => $drugId, 'quantity' => 12,
        ]);

        // فرع B (نفس المالك) يسحب لقطة مخزون A.
        $bRows = $this->pull('br_B')->assertOk()->json('tables.branch_inventory_snapshots.rows');
        $this->assertCount(1, $bRows);
        $this->assertSame('br_A', $bRows[0]['snapshot_branch_id']);
        $this->assertSame($drug, $bRows[0]['drug_id_uuid']); // FK متحوّل

        // فرع A لا يسحب لقطته الخاصة (owner_other_branches تستثني النفس).
        $aRows = $this->pull('br_A')->assertOk()->json('tables.branch_inventory_snapshots.rows');
        $this->assertCount(0, $aRows);
    }

    // ─────────── branches: تُسحب owner-scoped فقط ───────────

    public function test_branches_pull_is_owner_scoped(): void
    {
        $owner1 = $this->makeOwner();
        $owner2 = $this->makeOwner(['email' => 'other@test.com', 'uuid' => (string) Str::ulid()]);
        $this->makeBranch('br_A', 'A', $owner1);
        $this->makeBranch('br_B', 'B', $owner1);
        $this->makeBranch('br_X', 'X', $owner2); // لصيدلية أخرى

        $rows = $this->pull('br_A')->assertOk()->json('tables.branches.rows');
        $codes = array_column($rows, 'code');
        sort($codes);
        $this->assertSame(['A', 'B'], $codes); // فرع owner2 لا يظهر
    }
}
