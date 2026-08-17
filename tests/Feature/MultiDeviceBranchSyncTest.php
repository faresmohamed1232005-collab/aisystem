<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2ب (اتجاه B) — Stage 5: تكامل الحلقة الكاملة.
 *
 * الموقع يعرّف فرع A ويجهّز مخزونه (branch_id=A) → جهاز يسجّل بكود A (verify-exists) فيأخذ
 * نفس branch_id → يسحب مخزون فرعه عبر own_branch جاهزاً للعمل offline → جهاز ثانٍ بنفس الكود
 * يشارك الفرع. يثبت أن الأجهزة المتعددة للفرع الواحد ترى بيانات فرعها لا بيانات فرع آخر.
 */
class MultiDeviceBranchSyncTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-sync-token';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'sync.token' => $this->token,
            'sync.server_url' => 'https://server.test',
            'sync.register_requires_existing_branch' => true,
        ]);
    }

    private function headers(): array
    {
        return ['X-Sync-Token' => $this->token, 'Accept' => 'application/json'];
    }

    public function test_web_defined_branch_stock_reaches_its_devices_only(): void
    {
        // ── على «الموقع»: مالك + فرعان معرّفان + مخزون افتتاحي لكل فرع (branch_id مختلف) ──
        $owner = DB::table('users')->insertGetId([
            'name' => 'صيدلية', 'email' => 'owner@test.com', 'password' => Hash::make('secret123'),
            'is_approved' => 1, 'uuid' => (string) Str::ulid(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $branchA = 'br_' . Str::ulid();
        $branchB = 'br_' . Str::ulid();
        foreach ([[$branchA, 'A'], [$branchB, 'B']] as [$bid, $code]) {
            DB::table('branches')->insert([
                'branch_id' => $bid, 'code' => $code, 'user_id' => $owner, 'uuid' => (string) Str::ulid(),
                'status' => 'active', 'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $drug = (string) Str::ulid();
        $drugId = DB::table('drugs')->insertGetId(['uuid' => $drug, 'name_ar' => 'دواء', 'created_at' => now(), 'updated_at' => now()]);
        $now = now()->toDateTimeString();
        DB::table('user_drug_inventory')->insert([
            'uuid' => (string) Str::ulid(), 'user_id' => $owner, 'branch_id' => $branchA,
            'drug_id' => $drugId, 'quantity' => 30, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('user_drug_inventory')->insert([
            'uuid' => (string) Str::ulid(), 'user_id' => $owner, 'branch_id' => $branchB,
            'drug_id' => $drugId, 'quantity' => 99, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // ── جهاز #1 يسجّل بكود A (الفرع موجود) → يأخذ نفس branch_id ──
        $reg1 = $this->withHeaders($this->headers())->postJson('/api/sync/register', [
            'code' => 'A', 'device_no' => 1, 'owner_login' => 'owner@test.com', 'owner_password' => 'secret123',
        ])->assertOk();
        $this->assertSame($branchA, $reg1->json('branch_id'));

        // جهاز #2 بنفس الكود → نفس الفرع (يشاركان البيانات).
        $reg2 = $this->withHeaders($this->headers())->postJson('/api/sync/register', [
            'code' => 'A', 'device_no' => 2, 'owner_login' => 'owner@test.com', 'owner_password' => 'secret123',
        ])->assertOk();
        $this->assertSame($branchA, $reg2->json('branch_id'));

        // ── جهاز الفرع A يسحب مخزونه (own_branch): مخزون A فقط، مع ترجمة drug_id → uuid ──
        $pull = $this->withHeaders($this->headers())->postJson('/api/sync/pull', [
            'branch_id' => $branchA, 'cursors' => [],
        ])->assertOk();
        $rows = $pull->json('tables.user_drug_inventory.rows');
        $this->assertCount(1, $rows);                         // مخزون A فقط (ليس B)
        $this->assertSame(30.0, (float) $rows[0]['quantity']);
        $this->assertSame($drug, $rows[0]['drug_id_uuid']);   // FK متحوّل
        $this->assertSame($branchA, $rows[0]['branch_id']);
    }
}
