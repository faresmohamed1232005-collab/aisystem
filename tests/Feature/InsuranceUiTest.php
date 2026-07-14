<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\InsuranceRule;
use App\Models\InsuredPatient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 UI — يتأكد إن شاشات التأمين تُرندَر فعليًا (لا أخطاء runtime في الـ blade/controllers)
 * ومنطق CRUD الأساسي شغّال. SQLite :memory:.
 */
class InsuranceUiTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create([
            'name' => 'صيدلية', 'email' => 'owner@test.com',
            'password' => bcrypt('secret123'), 'is_approved' => 1,
        ]);
    }

    public function test_contract_pages_render_and_crud_works(): void
    {
        $user = $this->owner();

        // شاشة index وحدها فيها panel الإضافة + modal التعديل (نمط الموظفين).
        $this->actingAs($user)->get(route('contracts.index'))->assertOk()
            ->assertSee('add-form-wrap', false); // panel الإضافة موجود

        // إنشاء عقد تأمين → يُنشئ قاعدة تأمين افتراضية
        $this->actingAs($user)->post(route('contracts.store'), [
            'type' => 'insurance', 'name' => 'Globe Med', 'status' => 'active',
        ])->assertRedirect();

        $contract = Contract::where('user_id', $user->id)->firstOrFail();
        $this->assertNotNull($contract->insuranceRule, 'قاعدة تأمين افتراضية تُنشأ مع عقد التأمين');

        $this->actingAs($user)->get(route('contracts.show', $contract))->assertOk()->assertSee('قواعد التأمين');
        // modal التعديل موجود على index مع action الـ update للعقد.
        $this->actingAs($user)->get(route('contracts.index'))->assertOk()
            ->assertSee('edit-modal-'.$contract->id, false);

        // حفظ قواعد التأمين + قاعدة تسعير
        $this->actingAs($user)->post(route('contracts.insurance-rule', $contract), [
            'coverage_percent' => 80, 'patient_contribution_percent' => 20, 'approval_required' => 0,
        ])->assertRedirect();
        $this->assertEquals('80.00', $contract->fresh()->insuranceRule->coverage_percent);

        $this->actingAs($user)->post(route('contracts.pricing-rules.store', $contract), [
            'product_category' => 'medicines', 'discount_type' => 'percentage', 'discount_value' => 10,
        ])->assertRedirect();
        $this->assertEquals(1, $contract->pricingRules()->count());
    }

    public function test_insured_patient_pages_render_and_search(): void
    {
        $user = $this->owner();
        $contract = Contract::create([
            'user_id' => $user->id, 'code' => Contract::generateCode($user->id),
            'type' => 'insurance', 'name' => 'Globe Med', 'status' => 'active',
        ]);

        // شاشة index فيها panel الإضافة + modal التعديل (نمط الموظفين).
        $this->actingAs($user)->get(route('insured-patients.index'))->assertOk()
            ->assertSee('add-form-wrap', false);

        $this->actingAs($user)->post(route('insured-patients.store'), [
            'contract_id' => $contract->id, 'name' => 'أحمد', 'card_number' => 'C-1',
        ])->assertRedirect();

        $patient = InsuredPatient::where('user_id', $user->id)->firstOrFail();
        $this->actingAs($user)->get(route('insured-patients.index'))->assertOk()
            ->assertSee('data-update-url', false)
            ->assertSee('أحمد');

        // بحث AJAX مقيّد بالعقد
        $this->actingAs($user)
            ->get(route('insured-patients.search', ['contract_id' => $contract->id, 'q' => 'أحمد']))
            ->assertOk()->assertJsonFragment(['name' => 'أحمد']);
    }

    public function test_claims_pages_render_and_gather_unclaimed(): void
    {
        $user = $this->owner();
        $contract = Contract::create([
            'user_id' => $user->id, 'code' => Contract::generateCode($user->id),
            'type' => 'insurance', 'name' => 'Globe Med', 'status' => 'active',
        ]);

        // فاتورة تأمين مؤهّلة للمطالبة
        $sale = \App\Models\Sale::create([
            'user_id' => $user->id, 'invoice_number' => 'INV-1',
            'total' => 1000, 'discount' => 0, 'paid' => 200, 'remaining' => 0,
            'payment_method' => 'insurance', 'payment_status' => 'paid',
            'contract_id' => $contract->id, 'covered_amount' => 800, 'patient_amount' => 200,
        ]);

        $this->actingAs($user)->get(route('insurance-claims.index'))->assertOk();
        $this->actingAs($user)->get(route('insurance-claims.create', ['contract_id' => $contract->id]))
            ->assertOk()->assertSee('INV-1');

        // إنشاء مطالبة تجمّع الفاتورة
        $this->actingAs($user)->post(route('insurance-claims.store'), [
            'contract_id' => $contract->id, 'sale_ids' => [$sale->id],
        ])->assertRedirect();

        $claim = \App\Models\InsuranceClaim::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals('800.00', $claim->amount);
        $this->assertEquals(1, $claim->items()->count());

        $this->actingAs($user)->get(route('insurance-claims.show', $claim))->assertOk();

        // الفاتورة اتجمّعت → مبقتش unclaimed
        $this->actingAs($user)->get(route('insurance-claims.create', ['contract_id' => $contract->id]))
            ->assertOk()->assertDontSee('INV-1');
    }

    public function test_sale_create_screen_exposes_insurance_contracts(): void
    {
        $user = $this->owner();
        Contract::create([
            'user_id' => $user->id, 'code' => Contract::generateCode($user->id),
            'type' => 'insurance', 'name' => 'Globe Med', 'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('sales.create'))
            ->assertOk()
            ->assertSee('INSURANCE_CONTRACTS', false)
            ->assertSee('Globe Med');
    }
}
