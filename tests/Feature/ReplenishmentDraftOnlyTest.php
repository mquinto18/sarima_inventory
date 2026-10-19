<?php

namespace Tests\Feature;

use App\Mail\PurchaseOrderMail;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReplenishmentDraftOnlyTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        return $user;
    }

    private function makeProductWithSupplier(int $stock, int $reorderLevel = 5): Product
    {
        $supplier = Supplier::create([
            'name' => 'Test Supplier',
            'email' => 'supplier@example.com',
            'phone' => '0900000000',
            'address' => 'Somewhere',
            'lead_time_days' => 3,
            'active' => true,
        ]);

        $product = Product::create([
            'name' => 'Replenishment Test Product',
            'sku' => 'SKU-' . uniqid(),
            'category' => 'Medicine',
            'stock' => 0,
            'status' => 'In Stock',
            'price' => 20,
            'reorder_level' => $reorderLevel,
            'expiry_date' => null,
            'vat_type' => 'T',
            'senior_pwd_eligible' => true,
        ]);

        ProductSupplier::create([
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'cost_price' => 10,
            'lead_time_days' => 3,
            'is_primary' => true,
        ]);

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

    public function test_hitting_reorder_point_creates_draft_po_without_emailing_supplier(): void
    {
        Mail::fake();
        Setting::set('stp_enabled', true);
        Setting::set('stp_max_order_value', 5000);
        Setting::set('stp_max_qty_per_product', 500);

        $product = $this->makeProductWithSupplier(stock: 10, reorderLevel: 5);

        // Deduct down to the reorder point - this is what a POS sale or any
        // stock-decreasing event does, and should queue the replenishment
        // check via InventoryService.
        (new InventoryService())->deductStock($product, 5, 'pos_sale', null, null, null);

        $purchaseOrder = PurchaseOrder::where('is_auto_generated', true)->first();

        $this->assertNotNull($purchaseOrder, 'Expected an auto-generated PO to be created.');
        $this->assertEquals('draft', $purchaseOrder->status);
        $this->assertNull($purchaseOrder->sent_at);
        Mail::assertNothingSent();
    }

    public function test_admin_can_edit_draft_po_quantities_before_sending(): void
    {
        Mail::fake();
        $this->actingAdmin();
        Setting::set('stp_enabled', true);
        Setting::set('stp_max_order_value', 5000);
        Setting::set('stp_max_qty_per_product', 500);

        $product = $this->makeProductWithSupplier(stock: 10, reorderLevel: 5);
        (new InventoryService())->deductStock($product, 5, 'pos_sale', null, null, null);

        $purchaseOrder = PurchaseOrder::where('is_auto_generated', true)->with('items')->first();
        $item = $purchaseOrder->items->first();

        $response = $this->post(route('purchase-orders.update-items', $purchaseOrder->id), [
            'items' => [
                $item->id => ['quantity_ordered' => 50],
            ],
        ]);

        $response->assertRedirect();
        $purchaseOrder->refresh();
        $item->refresh();

        $this->assertEquals(50, $item->quantity_ordered);
        $this->assertEquals(500.00, (float) $item->subtotal); // 50 * cost_price(10)
        $this->assertEquals(500.00, (float) $purchaseOrder->total_value);
        $this->assertEquals('draft', $purchaseOrder->status);
        Mail::assertNothingSent();
    }

    public function test_send_to_supplier_requires_explicit_admin_action(): void
    {
        Mail::fake();
        $this->actingAdmin();
        Setting::set('stp_enabled', true);
        Setting::set('stp_max_order_value', 5000);
        Setting::set('stp_max_qty_per_product', 500);

        $product = $this->makeProductWithSupplier(stock: 10, reorderLevel: 5);
        (new InventoryService())->deductStock($product, 5, 'pos_sale', null, null, null);

        $purchaseOrder = PurchaseOrder::where('is_auto_generated', true)->first();

        // Still draft, still unsent, until the admin explicitly clicks Send.
        Mail::assertNothingSent();

        $response = $this->post(route('purchase-orders.send', $purchaseOrder->id));

        $response->assertRedirect();
        $purchaseOrder->refresh();
        $this->assertEquals('sent', $purchaseOrder->status);
        $this->assertNotNull($purchaseOrder->sent_at);
        Mail::assertSent(PurchaseOrderMail::class);
    }

    public function test_cannot_edit_quantities_once_sent(): void
    {
        Mail::fake();
        $this->actingAdmin();
        Setting::set('stp_enabled', true);
        Setting::set('stp_max_order_value', 5000);
        Setting::set('stp_max_qty_per_product', 500);

        $product = $this->makeProductWithSupplier(stock: 10, reorderLevel: 5);
        (new InventoryService())->deductStock($product, 5, 'pos_sale', null, null, null);

        $purchaseOrder = PurchaseOrder::where('is_auto_generated', true)->with('items')->first();
        $this->post(route('purchase-orders.send', $purchaseOrder->id));
        $purchaseOrder->refresh();

        $item = $purchaseOrder->items->first();
        $originalQty = $item->quantity_ordered;

        $response = $this->post(route('purchase-orders.update-items', $purchaseOrder->id), [
            'items' => [
                $item->id => ['quantity_ordered' => 999],
            ],
        ]);

        $response->assertRedirect();
        $item->refresh();
        $this->assertEquals($originalQty, $item->quantity_ordered);
    }
}
