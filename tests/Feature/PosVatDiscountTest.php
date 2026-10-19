<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosVatDiscountTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        return $user;
    }

    private function makeProduct(array $attributes = []): Product
    {
        $stock = $attributes['stock'] ?? 10;
        unset($attributes['stock']);

        $product = Product::create(array_merge([
            'name' => 'Test Product ' . uniqid(),
            'sku' => 'SKU-' . uniqid(),
            'category' => 'Medicine',
            'stock' => 0,
            'status' => 'In Stock',
            'price' => 57.75,
            'reorder_level' => 5,
            'expiry_date' => null,
            'vat_type' => 'T',
            'senior_pwd_eligible' => true,
        ], $attributes));

        (new InventoryService())->addStock(
            $product,
            $stock,
            'initial',
            null,
            null,
            'Test stock',
            now()->addYear()->format('Y-m-d'),
            null,
            'initial'
        );

        return $product->fresh();
    }

    private function checkout(array $items, float $tendered, ?array $discount = null)
    {
        return $this->postJson('/pos/checkout', [
            'items' => $items,
            'amount_tendered' => $tendered,
            'discount' => $discount,
        ]);
    }

    public function test_vat_mode_single_vatable_item(): void
    {
        $this->actingUser();
        Setting::set('tax_registration', 'VAT');
        Setting::set('vat_rate', 12);

        $product = $this->makeProduct(['price' => 57.75, 'vat_type' => 'T']);

        $response = $this->checkout(
            [['product_id' => $product->id, 'quantity' => 1]],
            57.75
        );

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'total_amount' => 57.75,
            'amount_due' => 57.75,
        ]);

        $sale = Sale::where('product_id', $product->id)->first();
        $this->assertEquals(51.56, (float) $sale->vatable_sales);
        $this->assertEquals(6.19, (float) $sale->vat_amount);
        $this->assertEquals(0, (float) $sale->vat_exempt_sales);
        $this->assertEquals(0, (float) $sale->zero_rated_sales);
        $this->assertEquals(57.75, (float) $sale->amount_due);
        $this->assertEquals('T', $sale->vat_type);
    }

    public function test_vat_mode_mixed_t_and_x_items(): void
    {
        $this->actingUser();
        Setting::set('tax_registration', 'VAT');
        Setting::set('vat_rate', 12);

        $vatable = $this->makeProduct(['price' => 57.75, 'vat_type' => 'T']);
        $exempt = $this->makeProduct(['price' => 40.00, 'vat_type' => 'X']);

        $response = $this->checkout(
            [
                ['product_id' => $vatable->id, 'quantity' => 1],
                ['product_id' => $exempt->id, 'quantity' => 1],
            ],
            97.75
        );

        $response->assertOk();

        $vatableSale = Sale::where('product_id', $vatable->id)->first();
        $exemptSale = Sale::where('product_id', $exempt->id)->first();

        $this->assertEquals(51.56, (float) $vatableSale->vatable_sales);
        $this->assertEquals(6.19, (float) $vatableSale->vat_amount);
        $this->assertEquals(0, (float) $vatableSale->vat_exempt_sales);

        $this->assertEquals(0, (float) $exemptSale->vatable_sales);
        $this->assertEquals(0, (float) $exemptSale->vat_amount);
        $this->assertEquals(40.00, (float) $exemptSale->vat_exempt_sales);

        $this->assertEquals(97.75, (float) ($vatableSale->amount_due + $exemptSale->amount_due));
    }

    public function test_non_vat_mode_has_no_vat_breakdown(): void
    {
        $this->actingUser();
        Setting::set('tax_registration', 'Non-VAT');

        $product = $this->makeProduct(['price' => 57.75, 'vat_type' => 'T']);

        $response = $this->checkout(
            [['product_id' => $product->id, 'quantity' => 1]],
            57.75
        );

        $response->assertOk();

        $sale = Sale::where('product_id', $product->id)->first();
        $this->assertEquals(0, (float) $sale->vatable_sales);
        $this->assertEquals(0, (float) $sale->vat_amount);
        $this->assertEquals(0, (float) $sale->vat_exempt_sales);
        $this->assertEquals(0, (float) $sale->zero_rated_sales);
        $this->assertEquals(57.75, (float) $sale->amount_due);
        $this->assertEquals('Non-VAT', $sale->tax_mode);
    }

    public function test_senior_discount_in_vat_mode(): void
    {
        $this->actingUser();
        Setting::set('tax_registration', 'VAT');
        Setting::set('vat_rate', 12);

        $product = $this->makeProduct(['price' => 57.75, 'vat_type' => 'T', 'senior_pwd_eligible' => true]);

        $response = $this->checkout(
            [['product_id' => $product->id, 'quantity' => 1]],
            41.25,
            ['type' => 'senior', 'id_number' => 'SC-001', 'customer_name' => 'Juan Dela Cruz']
        );

        $response->assertOk();
        $response->assertJson(['amount_due' => 41.25]);

        $sale = Sale::where('product_id', $product->id)->first();
        $this->assertEquals(41.25, (float) $sale->amount_due);
        $this->assertEquals(10.31, (float) $sale->discount_amount);
        $this->assertEquals(6.19, (float) $sale->discount_vat_exempt_amount);
        $this->assertEquals('senior', $sale->discount_type);
        $this->assertEquals('SC-001', $sale->discount_id_number);
        $this->assertEquals('Juan Dela Cruz', $sale->discount_customer_name);
    }

    public function test_pwd_discount_in_non_vat_mode(): void
    {
        $this->actingUser();
        Setting::set('tax_registration', 'Non-VAT');

        $product = $this->makeProduct(['price' => 57.75, 'vat_type' => 'T', 'senior_pwd_eligible' => true]);

        $response = $this->checkout(
            [['product_id' => $product->id, 'quantity' => 1]],
            46.20,
            ['type' => 'pwd', 'id_number' => 'PWD-002', 'customer_name' => 'Maria Santos']
        );

        $response->assertOk();
        $response->assertJson(['amount_due' => 46.20]);

        $sale = Sale::where('product_id', $product->id)->first();
        $this->assertEquals(46.20, (float) $sale->amount_due);
        $this->assertEquals(11.55, (float) $sale->discount_amount);
        $this->assertEquals(0, (float) $sale->discount_vat_exempt_amount);
        $this->assertEquals('pwd', $sale->discount_type);
    }

    public function test_inventory_is_deducted_correctly_after_discounted_sale(): void
    {
        $this->actingUser();
        Setting::set('tax_registration', 'VAT');
        Setting::set('vat_rate', 12);

        $product = $this->makeProduct(['price' => 57.75, 'vat_type' => 'T', 'stock' => 10]);
        $this->assertEquals(10, $product->fresh()->stock);

        $response = $this->checkout(
            [['product_id' => $product->id, 'quantity' => 3]],
            123.75,
            ['type' => 'senior', 'id_number' => 'SC-003', 'customer_name' => 'Pedro Reyes']
        );

        $response->assertOk();

        $this->assertEquals(7, $product->fresh()->stock);

        $sale = Sale::where('product_id', $product->id)->first();
        $this->assertEquals(3, $sale->quantity_sold);
    }
}
