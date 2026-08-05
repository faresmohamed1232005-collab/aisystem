<?php

namespace Tests\Feature;

use App\Models\Drug;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalePrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_print_a4_and_80mm_receipt(): void
    {
        [$user, $sale] = $this->makeSale();

        $this->actingAs($user)
            ->get(route('sales.print', ['sale' => $sale, 'format' => 'a4']))
            ->assertOk()
            ->assertSee('طباعة A4');

        $this->actingAs($user)
            ->get(route('sales.print', ['sale' => $sale, 'format' => 'receipt']))
            ->assertOk()
            ->assertSee('Receipt 80mm')
            ->assertSee('@page { size: 80mm auto;', false)
            ->assertSee($sale->invoice_number)
            ->assertSee('دواء للطباعة');
    }

    public function test_print_format_is_validated_and_other_owner_is_forbidden(): void
    {
        [$user, $sale] = $this->makeSale();

        $this->actingAs($user)
            ->get(route('sales.print', ['sale' => $sale, 'format' => 'letter']))
            ->assertSessionHasErrors('format');

        $other = User::factory()->create(['is_approved' => 1]);
        $this->actingAs($other)
            ->get(route('sales.print', ['sale' => $sale, 'format' => 'receipt']))
            ->assertForbidden();
    }

    private function makeSale(): array
    {
        $user = User::factory()->create([
            'is_approved' => 1,
            'pharmacy_name' => 'صيدلية الاختبار',
        ]);
        $drug = Drug::create([
            'name_ar' => 'دواء للطباعة',
            'new_price' => 25,
        ]);
        $sale = Sale::create([
            'user_id' => $user->id,
            'invoice_number' => 'T-INV-1001',
            'total' => 50,
            'discount' => 0,
            'paid' => 50,
            'remaining' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'drug_id' => $drug->id,
            'quantity' => 2,
            'unit_name' => 'علبة',
            'unit_price' => 25,
            'price' => 25,
            'subtotal' => 50,
        ]);

        return [$user, $sale];
    }
}
