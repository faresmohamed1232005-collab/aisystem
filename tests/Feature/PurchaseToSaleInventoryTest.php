<?php

namespace Tests\Feature;

use App\Models\Drug;
use App\Models\User;
use App\Models\UserDrugInventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseToSaleInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sync.branch_id' => 'br_TEST', 'sync.branch_code' => 'T']);
    }

    public function test_purchased_product_is_available_in_direct_sale_and_can_be_sold(): void
    {
        $user = User::factory()->create(['is_approved' => 1]);

        $this->actingAs($user)->postJson('/purchases', [
            'invoice_number' => 'PUR-001',
            'invoice_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'paid' => 500,
            'items' => [[
                'product_name' => 'دواء اختبار',
                'barcode' => 'TEST-001',
                'purchase_price' => 50,
                'selling_price' => 75,
                'quantity' => 10,
                'expiry_date' => now()->addYear()->startOfMonth()->toDateString(),
                'major_units' => 1,
                'minor_units' => 1,
            ]],
        ])->assertOk()->assertJson(['success' => true]);

        $drug = Drug::where('barcode', 'TEST-001')->firstOrFail();

        $this->assertDatabaseHas('user_drug_inventory', [
            'user_id' => $user->id,
            'branch_id' => 'br_TEST',
            'drug_id' => $drug->id,
            'quantity' => 10,
        ]);

        $search = $this->actingAs($user)->getJson('/products-search?q=' . urlencode('دواء اختبار'))
            ->assertOk();

        $product = collect($search->json())->firstWhere('id', $drug->id);
        $this->assertNotNull($product);
        $this->assertSame(10.0, (float) $product['quantity']);
        $this->assertTrue($product['in_my_inventory']);

        $this->actingAs($user)->postJson('/sales', [
            'items' => [[
                'id' => $drug->id,
                'qty' => 2,
                'qty_factor' => 1,
                'unit_price' => 75,
            ]],
            'payment_method' => 'cash',
            'paid' => 150,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_drug_inventory', [
            'user_id' => $user->id,
            'branch_id' => 'br_TEST',
            'drug_id' => $drug->id,
            'quantity' => 8,
        ]);
    }

    public function test_purchase_normalizes_mmyy_and_persists_expiry_on_item_and_inventory(): void
    {
        $user = User::factory()->create(['is_approved' => 1]);

        $response = $this->actingAs($user)->postJson('/purchases', [
            'invoice_number' => 'PUR-MMYY',
            'payment_method' => 'cash',
            'paid' => 500,
            'items' => [[
                'product_name' => 'دواء صلاحية مختصرة',
                'barcode' => 'MMYY-001',
                'purchase_price' => 50,
                'selling_price' => 70,
                'quantity' => 10,
                'expiry_date' => '0229',
            ]],
        ])->assertOk()->assertJson(['success' => true]);

        $drug = Drug::where('barcode', 'MMYY-001')->firstOrFail();
        $invoiceId = \App\Models\PurchaseInvoice::where('invoice_number', 'PUR-MMYY')->value('id');

        $item = \App\Models\PurchaseInvoiceItem::where('purchase_invoice_id', $invoiceId)->firstOrFail();
        $inventory = UserDrugInventory::where('user_id', $user->id)
            ->where('branch_id', 'br_TEST')
            ->where('drug_id', $drug->id)
            ->firstOrFail();

        $this->assertSame($drug->id, $item->drug_id);
        $this->assertSame('2029-02-01', $item->expiry_date->toDateString());
        $this->assertSame('2029-02-01', $inventory->expiry_date->toDateString());
        $this->assertSame(10.0, $inventory->quantity);
    }

    public function test_purchase_rejects_invalid_mmyy_without_writing_inventory(): void
    {
        $user = User::factory()->create(['is_approved' => 1]);

        $this->actingAs($user)->postJson('/purchases', [
            'invoice_number' => 'PUR-BAD-MMYY',
            'payment_method' => 'cash',
            'paid' => 500,
            'items' => [[
                'product_name' => 'دواء تاريخ خاطئ',
                'purchase_price' => 50,
                'selling_price' => 70,
                'quantity' => 10,
                'expiry_date' => '1329',
            ]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items.0.expiry_date');

        $this->assertDatabaseMissing('purchase_invoices', ['invoice_number' => 'PUR-BAD-MMYY']);
        $this->assertDatabaseCount('user_drug_inventory', 0);
    }

    public function test_search_hides_whitespace_duplicate_and_purchase_reuses_catalog_drug(): void
    {
        $user = User::factory()->create(['is_approved' => 1]);
        $catalogDrug = Drug::create([
            'name_ar' => ' دواء كتالوج',
            'name_en' => ' دواء كتالوج',
            'barcode' => ' 6220000012345',
            'new_price' => 70,
        ]);

        $this->actingAs($user)->postJson('/purchases', [
            'invoice_number' => 'PUR-002',
            'payment_method' => 'cash',
            'paid' => 500,
            'items' => [[
                'product_name' => 'دواء كتالوج',
                'barcode' => '6220000012345',
                'purchase_price' => 50,
                'selling_price' => 70,
                'quantity' => 10,
                'expiry_date' => now()->addYear()->startOfMonth()->toDateString(),
            ]],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(1, Drug::whereRaw('TRIM(barcode) = ?', ['6220000012345'])->count());
        $this->assertDatabaseHas('user_drug_inventory', [
            'user_id' => $user->id,
            'branch_id' => 'br_TEST',
            'drug_id' => $catalogDrug->id,
            'quantity' => 10,
        ]);

        $duplicate = Drug::create([
            'name_ar' => 'دواء كتالوج',
            'name_en' => 'دواء كتالوج',
            'barcode' => '6220000012345',
            'new_price' => 70,
        ]);

        $results = $this->actingAs($user)
            ->getJson('/products-search?q=' . urlencode('دواء كتالوج'))
            ->assertOk()
            ->json();

        $this->assertCount(1, $results);
        $this->assertSame($catalogDrug->id, $results[0]['id']);
        $this->assertSame(10.0, (float) $results[0]['quantity']);
        $this->assertNotSame($duplicate->id, $results[0]['id']);
    }

    public function test_expired_batch_does_not_hide_or_supply_a_saleable_batch(): void
    {
        $user = User::factory()->create(['is_approved' => 1]);
        $drug = Drug::create([
            'name_ar' => 'دواء متعدد التشغيلات',
            'barcode' => 'TEST-002',
            'new_price' => 100,
            'major_units' => 1,
            'minor_units' => 1,
        ]);

        UserDrugInventory::create([
            'user_id' => $user->id,
            'branch_id' => 'br_TEST',
            'drug_id' => $drug->id,
            'quantity' => 20,
            'custom_price' => 90,
            'expiry_date' => now()->subMonth()->startOfMonth(),
        ]);
        UserDrugInventory::create([
            'user_id' => $user->id,
            'branch_id' => 'br_TEST',
            'drug_id' => $drug->id,
            'quantity' => 5,
            'custom_price' => 100,
            'expiry_date' => now()->addYear()->startOfMonth(),
        ]);

        $search = $this->actingAs($user)->getJson('/products-search?q=' . urlencode('متعدد التشغيلات'))
            ->assertOk();

        $product = collect($search->json())->firstWhere('id', $drug->id);
        $this->assertSame(5.0, (float) $product['quantity']);
        $this->assertSame(100.0, (float) $product['price']);
        $this->assertTrue($product['in_my_inventory']);

        $this->actingAs($user)->postJson('/sales', [
            'items' => [[
                'id' => $drug->id,
                'qty' => 6,
                'qty_factor' => 1,
                'unit_price' => 100,
            ]],
            'payment_method' => 'cash',
            'paid' => 600,
        ])->assertUnprocessable()->assertJson(['success' => false]);

        $this->assertSame(20.0, (float) UserDrugInventory::where('drug_id', $drug->id)
            ->whereDate('expiry_date', now()->subMonth()->startOfMonth())
            ->value('quantity'));
        $this->assertSame(5.0, (float) UserDrugInventory::where('drug_id', $drug->id)
            ->whereDate('expiry_date', now()->addYear()->startOfMonth())
            ->value('quantity'));
    }
}
