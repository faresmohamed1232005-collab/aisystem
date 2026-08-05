<?php

namespace Tests\Feature;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasePrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_print_purchase_as_a4_and_receipt(): void
    {
        [$user, $invoice] = $this->makeInvoice();

        $this->actingAs($user)
            ->get(route('purchases.print', ['invoice' => $invoice, 'format' => 'a4']))
            ->assertOk()
            ->assertSee('طباعة A4');

        $this->actingAs($user)
            ->get(route('purchases.print', ['invoice' => $invoice, 'format' => 'receipt']))
            ->assertOk()
            ->assertSee('طباعة Receipt 80mm')
            ->assertSee('@page { size: 80mm auto;', false)
            ->assertSee('دواء مشتريات')
            ->assertSee('01/02/2029');
    }

    public function test_purchase_print_requires_auth_valid_format_and_ownership(): void
    {
        [$user, $invoice] = $this->makeInvoice();

        $this->get(route('purchases.print', ['invoice' => $invoice]))
            ->assertRedirect(route('login'));

        $this->actingAs($user)
            ->get(route('purchases.print', ['invoice' => $invoice, 'format' => 'letter']))
            ->assertSessionHasErrors('format');

        $other = User::factory()->create(['is_approved' => 1]);
        $this->actingAs($other)
            ->get(route('purchases.print', ['invoice' => $invoice, 'format' => 'receipt']))
            ->assertForbidden();
    }

    private function makeInvoice(): array
    {
        $user = User::factory()->create(['is_approved' => 1, 'pharmacy_name' => 'صيدلية الاختبار']);
        $supplier = Supplier::create([
            'user_id' => $user->id,
            'code' => 'SUP-0001',
            'name' => 'مورد الاختبار',
        ]);
        $invoice = PurchaseInvoice::create([
            'user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'PUR-PRINT-1',
            'invoice_date' => now()->toDateString(),
            'total' => 100,
            'discount' => 0,
            'extra' => 0,
            'net_total' => 100,
            'paid' => 100,
            'remaining' => 0,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);
        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'product_name' => 'دواء مشتريات',
            'purchase_price' => 50,
            'selling_price' => 70,
            'quantity' => 2,
            'expiry_date' => '2029-02-01',
            'subtotal' => 100,
        ]);

        return [$user, $invoice];
    }
}
