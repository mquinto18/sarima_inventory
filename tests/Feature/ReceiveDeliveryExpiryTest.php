<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiveDeliveryExpiryTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        return $user;
    }

    private function makeConfirmedPoWithItem(): array
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
            'name' => 'Receiving Test Product',
            'sku' => 'SKU-' . uniqid(),
            'category' => 'Medicine',
            'stock' => 0,
            'status' => 'In Stock',
            'price' => 20,
            'reorder_level' => 5,
            'expiry_date' => null,
            'vat_type' => 'T',
            'senior_pwd_eligible' => true,
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-TEST-' . uniqid(),
            'supplier_id' => $supplier->id,
            'status' => 'confirmed',
            'is_auto_generated' => false,
            'total_value' => 200,
            'expected_delivery_date' => now()->addDays(3),
            'created_by' => null,
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity_ordered' => 10,
            'quantity_received' => 0,
            'unit_cost' => 20,
            'subtotal' => 200,
        ]);

        return [$purchaseOrder, $item, $product];
    }

    public function test_rejects_already_expired_expiry_date(): void
    {
        $this->actingAdmin();
        [$purchaseOrder, $item, $product] = $this->makeConfirmedPoWithItem();

        $response = $this->post(route('purchase-orders.receive', $purchaseOrder->id), [
            'items' => [
                $item->id => [
                    'quantity_received' => 5,
                    'expiry_date' => now()->subDays(10)->format('Y-m-d'),
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items.' . $item->id . '.expiry_date');
        $this->assertEquals(0, $item->fresh()->quantity_received);
        $this->assertEquals(0, $product->fresh()->stock);
    }

    public function test_rejects_expiry_date_less_than_three_months_out(): void
    {
        $this->actingAdmin();
        [$purchaseOrder, $item, $product] = $this->makeConfirmedPoWithItem();

        $response = $this->post(route('purchase-orders.receive', $purchaseOrder->id), [
            'items' => [
                $item->id => [
                    'quantity_received' => 5,
                    // One month from now - not expired, but still too soon.
                    'expiry_date' => now()->addMonth()->format('Y-m-d'),
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items.' . $item->id . '.expiry_date');
        $this->assertEquals(0, $item->fresh()->quantity_received);
        $this->assertEquals(0, $product->fresh()->stock);
    }

    public function test_accepts_expiry_date_exactly_three_months_out(): void
    {
        $this->actingAdmin();
        [$purchaseOrder, $item, $product] = $this->makeConfirmedPoWithItem();

        $response = $this->post(route('purchase-orders.receive', $purchaseOrder->id), [
            'items' => [
                $item->id => [
                    'quantity_received' => 5,
                    'expiry_date' => now()->addMonths(3)->format('Y-m-d'),
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(5, $item->fresh()->quantity_received);
        $this->assertEquals(5, $product->fresh()->stock);
    }

    public function test_accepts_expiry_date_well_beyond_three_months(): void
    {
        $this->actingAdmin();
        [$purchaseOrder, $item, $product] = $this->makeConfirmedPoWithItem();

        $response = $this->post(route('purchase-orders.receive', $purchaseOrder->id), [
            'items' => [
                $item->id => [
                    'quantity_received' => 10,
                    'expiry_date' => now()->addYear()->format('Y-m-d'),
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(10, $item->fresh()->quantity_received);
        $this->assertEquals(10, $product->fresh()->stock);
    }
}
