<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\InsuranceRule;
use App\Services\Insurance\ContractPricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 1 — التعاقدات والتأمين: محرك التسعير + مزامنة owner-scoped + ترجمة FK + push المطالبات.
 * SQLite :memory: (معزول). يمتد على نمط SyncTest.
 */
class InsuranceTest extends TestCase
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

    private function makeInsuranceContract(int $userId, float $coverage, ?float $max = null, array $ruleExtra = []): Contract
    {
        $contract = Contract::create([
            'user_id' => $userId, 'code' => Contract::generateCode($userId),
            'type' => 'insurance', 'name' => 'Globe Med', 'status' => 'active',
        ]);
        InsuranceRule::create(array_merge([
            'user_id' => $userId, 'contract_id' => $contract->id,
            'coverage_percent' => $coverage, 'patient_contribution_percent' => 100 - $coverage,
            'max_per_prescription' => $max,
        ], $ruleExtra));
        return $contract->load('insuranceRule');
    }

    // ─────────────── Contract Pricing Engine ───────────────

    public function test_engine_splits_covered_and_patient_by_coverage_percent(): void
    {
        $owner = $this->makeOwner();
        $contract = $this->makeInsuranceContract($owner, 80);

        $r = app(ContractPricingEngine::class)->split($contract, 1000);

        $this->assertEqualsWithDelta(800.0, $r['covered'], 0.001);
        $this->assertEqualsWithDelta(200.0, $r['patient'], 0.001);
        $this->assertFalse($r['needs_approval']);
    }

    public function test_engine_caps_covered_at_max_per_prescription(): void
    {
        $owner = $this->makeOwner();
        $contract = $this->makeInsuranceContract($owner, 80, max: 500); // 80% من 1000 = 800، لكن السقف 500

        $r = app(ContractPricingEngine::class)->split($contract, 1000);

        $this->assertEqualsWithDelta(500.0, $r['covered'], 0.001);
        $this->assertEqualsWithDelta(500.0, $r['patient'], 0.001);
        $this->assertTrue($r['capped']);
    }

    public function test_engine_flags_needs_approval_over_limit(): void
    {
        $owner = $this->makeOwner();
        $contract = $this->makeInsuranceContract($owner, 80, ruleExtra: [
            'approval_required' => true, 'approval_amount_limit' => 2000,
        ]);

        $this->assertFalse(app(ContractPricingEngine::class)->split($contract, 1500)['needs_approval']);
        $this->assertTrue(app(ContractPricingEngine::class)->split($contract, 2500)['needs_approval']);
    }

    public function test_engine_non_insurance_contract_patient_pays_all(): void
    {
        $owner = $this->makeOwner();
        $contract = Contract::create([
            'user_id' => $owner, 'code' => Contract::generateCode($owner),
            'type' => 'government', 'name' => 'وزارة الصحة', 'status' => 'active',
        ]);

        $r = app(ContractPricingEngine::class)->split($contract, 1000);
        $this->assertEqualsWithDelta(0.0, $r['covered'], 0.001);
        $this->assertEqualsWithDelta(1000.0, $r['patient'], 0.001);
    }

    // ─────────────── مزامنة: pull owner-scoped + ترجمة FK ───────────────

    public function test_pull_contracts_scoped_to_owner_with_fk_translation(): void
    {
        $owner = $this->makeOwner();
        $ownerUuid = DB::table('users')->where('id', $owner)->value('uuid');
        $other = $this->makeOwner(['email' => 'other@test.com', 'uuid' => (string) Str::ulid()]);

        $mine  = $this->makeInsuranceContract($owner, 70);
        $theirs = $this->makeInsuranceContract($other, 50);

        $branchId = 'br_OWNER';
        DB::table('branches')->insert([
            'branch_id' => $branchId, 'code' => 'A', 'user_id' => $owner,
            'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $res = $this->withHeaders($this->headers())->postJson('/api/sync/pull', [
            'branch_id' => $branchId, 'cursors' => [],
        ])->assertOk();

        // العقود: فقط عقد المالك.
        $contracts = $res->json('tables.contracts.rows');
        $this->assertCount(1, $contracts);
        $this->assertSame($mine->code, $contracts[0]['code']);
        $this->assertArrayNotHasKey('id', $contracts[0]); // ليس preserve_id

        // insurance_rules: قاعدة عقد المالك فقط، وcontract_id متحوّل إلى contract_id_uuid.
        $rules = $res->json('tables.insurance_rules.rows');
        $this->assertCount(1, $rules);
        $this->assertSame($mine->uuid, $rules[0]['contract_id_uuid']);
        $this->assertArrayNotHasKey('contract_id', $rules[0]);
    }

    // ─────────────── مزامنة: push المطالبات (الفرع master) ───────────────

    public function test_push_creates_insurance_claim_with_translated_fks(): void
    {
        $owner = $this->makeOwner();

        // عقد + فاتورة موجودان على السيرفر (بنفس uuid اللي الفرع هيبعته).
        $contract = $this->makeInsuranceContract($owner, 80);
        $saleUuid = (string) Str::ulid();
        DB::table('sales')->insert([
            'uuid' => $saleUuid, 'user_id' => $owner, 'invoice_number' => 'A-INV-1',
            'total' => 1000, 'paid' => 200, 'payment_method' => 'insurance',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $saleId = DB::table('sales')->where('uuid', $saleUuid)->value('id');

        $claimUuid = (string) Str::ulid();
        $itemUuid  = (string) Str::ulid();

        $res = $this->withHeaders($this->headers())->postJson('/api/sync/push', [
            'branch_id' => 'br_A',
            'tables' => [
                'insurance_claims' => [[
                    'uuid' => $claimUuid, 'user_id' => $owner, 'contract_id_uuid' => $contract->uuid,
                    'claim_number' => 'CLM-0001', 'amount' => 800, 'status' => 'draft',
                    'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString(),
                ]],
                'insurance_claim_items' => [[
                    'uuid' => $itemUuid, 'user_id' => $owner, 'claim_id_uuid' => $claimUuid,
                    'sale_id_uuid' => $saleUuid, 'covered_amount' => 800,
                    'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString(),
                ]],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        // المطالبة طُبّقت وربطت بالعقد الصحيح (ترجمة contract_id_uuid → id).
        $claimId = DB::table('insurance_claims')->where('uuid', $claimUuid)->value('id');
        $this->assertNotNull($claimId);
        $this->assertSame($contract->id, (int) DB::table('insurance_claims')->where('id', $claimId)->value('contract_id'));

        // بند المطالبة ربط بالمطالبة والفاتورة الصحيحين.
        $this->assertDatabaseHas('insurance_claim_items', [
            'uuid' => $itemUuid, 'claim_id' => $claimId, 'sale_id' => $saleId,
        ]);
        $this->assertContains($claimUuid, $res->json('detail.insurance_claims.accepted_uuids'));
    }

    // ─────────────── تكامل البيع: محرك التسعير يطبّق تلقائياً ───────────────

    public function test_insurance_sale_computes_covered_and_patient_amounts(): void
    {
        $user = \App\Models\User::factory()->create(['is_approved' => 1]);
        $contract = $this->makeInsuranceContract($user->id, 80);

        $drugId = DB::table('drugs')->insertGetId([
            'uuid' => (string) Str::ulid(), 'name_ar' => 'كونجستال', 'new_price' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        \App\Models\UserDrugInventory::create([
            'user_id' => $user->id, 'drug_id' => $drugId, 'quantity' => 100,
            'custom_price' => 100, 'cost_price' => 60,
        ]);

        $res = $this->actingAs($user)->postJson('/sales', [
            'items' => [['id' => $drugId, 'qty' => 10, 'unit_price' => 100, 'qty_factor' => 1]],
            'payment_method' => 'insurance',
            'contract_id'    => $contract->id,
            'paid'           => 200,
        ]);

        $res->assertOk()->assertJson(['success' => true]);

        // فاتورة 1000، تغطية 80% → التأمين 800 / المريض 200.
        $sale = DB::table('sales')->where('id', $res->json('sale_id'))->first();
        $this->assertEqualsWithDelta(800.0, (float) $sale->covered_amount, 0.001);
        $this->assertEqualsWithDelta(200.0, (float) $sale->patient_amount, 0.001);
        $this->assertSame($contract->id, (int) $sale->contract_id);
    }
}
