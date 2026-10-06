<?php

namespace App\Services;

use App\Http\Controllers\ProductController;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReplenishmentService
{
    /**
     * Decide whether a product needs reordering right now. Shared by the
     * scheduled batch check (CheckReplenishment) and the event-driven check
     * fired right after a stock mutation (InventoryService::adjustStock),
     * so both paths apply identical rules.
     */
    public function evaluateForOrder(Product $product): array
    {
        $reorderPoint = ProductController::calculateDynamicReorderPoint($product->id);

        if ($product->stock > $reorderPoint) {
            return ['eligible' => false, 'reason' => 'above_reorder_point'];
        }

        $hasOpenOrder = PurchaseOrderItem::where('product_id', $product->id)
            ->whereHas('purchaseOrder', fn ($q) => $q->whereIn('status', ['draft', 'sent', 'confirmed']))
            ->exists();

        if ($hasOpenOrder) {
            Log::info("Replenishment: skipping product #{$product->id} ({$product->name}) - already has an open PO.");
            return ['eligible' => false, 'reason' => 'open_order'];
        }

        $supplier = $product->primarySupplier();

        if (!$supplier) {
            Log::warning("Replenishment: product #{$product->id} ({$product->name}) is at/below reorder point but has no primary supplier.");
            return ['eligible' => false, 'reason' => 'no_supplier'];
        }

        $link = ProductSupplier::where('product_id', $product->id)
            ->where('supplier_id', $supplier->id)
            ->where('is_primary', true)
            ->first();

        if (!$link) {
            Log::warning("Replenishment: product #{$product->id} ({$product->name}) has no primary product_supplier link despite primarySupplier() returning a supplier.");
            return ['eligible' => false, 'reason' => 'no_supplier'];
        }

        $forecastedDemand = ProductController::getForecastedDemand($product->id);
        $qty = (int) round(max($forecastedDemand, $reorderPoint - $product->stock));

        if ($qty <= 0) {
            return ['eligible' => false, 'reason' => 'zero_qty'];
        }

        $maxQty = (int) Setting::get('stp_max_qty_per_product');
        $maxValue = (float) Setting::get('stp_max_order_value');

        $capped = false;

        if ($qty > $maxQty) {
            $qty = $maxQty;
            $capped = true;
        }

        $orderValue = $qty * $link->cost_price;

        if ($orderValue > $maxValue) {
            $qty = $link->cost_price > 0 ? (int) floor($maxValue / $link->cost_price) : 0;
            $capped = true;
        }

        if ($qty <= 0) {
            Log::info("Replenishment: skipped_cap - product #{$product->id} ({$product->name}) quantity fell to 0 after applying safety caps.");
            return ['eligible' => false, 'reason' => 'zero_qty'];
        }

        return [
            'eligible' => true,
            'capped' => $capped,
            'supplier' => $supplier,
            'line' => [
                'product' => $product,
                'qty' => $qty,
                'cost_price' => $link->cost_price,
                'lead_time_days' => $link->lead_time_days,
            ],
        ];
    }

    /**
     * Create a draft PO for the given supplier + line items and immediately
     * try to email it. Sending is outside the row-creation transaction so a
     * mail/PDF failure never rolls back the already-decided PO - it's just
     * left as 'draft' for manual follow-up via "Send to Supplier".
     */
    /**
     * Next PO number for today, based on the highest suffix already
     * assigned - not a row count. A count breaks the moment any of today's
     * POs get deleted (e.g. test data cleanup): the count drops below the
     * highest number actually in use, so "count + 1" recomputes a number
     * that already belongs to a real, still-existing PO.
     */
    private function generatePoNumber(): string
    {
        $prefix = 'PO-' . now()->format('Ymd') . '-';

        $maxSuffix = PurchaseOrder::where('po_number', 'like', $prefix . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(po_number, -4) AS UNSIGNED)) as max_suffix')
            ->value('max_suffix');

        return $prefix . str_pad((string) (($maxSuffix ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Creates the PO for whichever lines still need one once this method has
     * exclusive claim on their products, or null if none do. Returns null
     * (rather than a PurchaseOrder) when every line got dropped by the
     * re-check below - callers must handle that case.
     */
    public function createAndSendPO(Supplier $supplier, array $lines): ?PurchaseOrder
    {
        $purchaseOrder = DB::transaction(function () use ($supplier, $lines) {
            // evaluateForOrder()'s own "already has an open PO" check runs
            // earlier, unlocked - two near-simultaneous triggers for the
            // same product (the 15-min sweep and an event-driven sale, say)
            // can both pass it before either creates a PO, duplicating the
            // order. Locking each product row here serializes that: the
            // second transaction to reach a given product waits for the
            // first to commit, then this re-check correctly sees the
            // already-created PO and drops that line instead of duplicating
            // it.
            $confirmedLines = [];

            foreach ($lines as $line) {
                Product::where('id', $line['product']->id)->lockForUpdate()->first();

                $hasOpenOrder = PurchaseOrderItem::where('product_id', $line['product']->id)
                    ->whereHas('purchaseOrder', fn ($q) => $q->whereIn('status', ['draft', 'sent', 'confirmed']))
                    ->exists();

                if ($hasOpenOrder) {
                    Log::info("Replenishment: dropped product #{$line['product']->id} ({$line['product']->name}) from new PO - another process already created an open order for it.");
                    continue;
                }

                $confirmedLines[] = $line;
            }

            if (empty($confirmedLines)) {
                return null;
            }

            $lines = $confirmedLines;

            $leadTimeDays = null;
            foreach ($lines as $line) {
                if (!empty($line['lead_time_days'])) {
                    $leadTimeDays = $line['lead_time_days'];
                    break;
                }
            }
            $leadTimeDays = $leadTimeDays ?? $supplier->lead_time_days ?? Setting::get('default_lead_time_days', 7);

            // Retried, not computed once: two near-simultaneous auto-orders
            // can still both land on the same next number between the MAX
            // lookup and the insert. Retrying with a freshly recomputed
            // number on a unique-constraint violation is simpler than
            // locking a counter row, and this is the only place PO numbers
            // are ever generated.
            $attempt = 0;
            while (true) {
                $attempt++;

                try {
                    $purchaseOrder = PurchaseOrder::create([
                        'po_number' => $this->generatePoNumber(),
                        'supplier_id' => $supplier->id,
                        'status' => 'draft',
                        'is_auto_generated' => true,
                        'total_value' => 0,
                        'expected_delivery_date' => now()->addDays($leadTimeDays),
                        'created_by' => null,
                    ]);
                    break;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($attempt >= 3 || !str_contains($e->getMessage(), 'po_number_unique')) {
                        throw $e;
                    }
                }
            }

            $totalValue = 0;

            foreach ($lines as $line) {
                $subtotal = $line['qty'] * $line['cost_price'];
                $totalValue += $subtotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $line['product']->id,
                    'quantity_ordered' => $line['qty'],
                    'quantity_received' => 0,
                    'unit_cost' => $line['cost_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            $purchaseOrder->update(['total_value' => $totalValue]);

            return $purchaseOrder;
        });

        if (!$purchaseOrder) {
            return null;
        }

        // Sending failures are swallowed here (logged only) so they never
        // undo the already-decided, already-capped PO above - it's just
        // left as 'draft' for manual follow-up via "Send to Supplier".
        // Callers can tell success from failure via $purchaseOrder->status.
        try {
            app(PurchaseOrderService::class)->sendToSupplier($purchaseOrder);
        } catch (\Throwable $e) {
            Log::error("Failed to send PO {$purchaseOrder->po_number}: " . $e->getMessage());
        }

        return $purchaseOrder->refresh();
    }

    /**
     * Request-scoped batch: every product whose stock decreases gets queued
     * here instead of evaluated immediately, so a single checkout/request
     * that drops several products to/below their reorder point produces one
     * combined email per supplier instead of one email per product. Static
     * because InventoryService resolves a fresh ReplenishmentService via
     * app() on every call; a plain PHP-FPM/artisan-serve request is still a
     * single process, so this naturally starts empty on the next request.
     */
    private static array $pendingProducts = [];
    private static bool $flushScheduled = false;

    /**
     * Event-driven entry point. Called right after a product's stock
     * decreases (see InventoryService::adjustStock) so a sale, adjustment,
     * or disposal that pushes it to/below its reorder point gets picked up
     * without waiting for the next scheduled CheckReplenishment run. Queues
     * the product and defers the actual evaluation/PO/email until the
     * current transaction commits (via DB::afterCommit), by which point
     * every product touched in this request has been queued - so they can
     * be grouped by supplier into one PO/email each, same as the scheduled
     * batch job already does across a full product scan.
     */
    public function queueProduct(Product $product): void
    {
        if (!Setting::get('stp_enabled', false)) {
            return;
        }

        self::$pendingProducts[$product->id] = $product;

        if (!self::$flushScheduled) {
            self::$flushScheduled = true;
            DB::afterCommit(fn () => $this->flushQueuedProducts());
        }
    }

    private function flushQueuedProducts(): void
    {
        $products = array_values(self::$pendingProducts);
        self::$pendingProducts = [];
        self::$flushScheduled = false;

        $bySupplier = [];

        foreach ($products as $product) {
            $result = $this->evaluateForOrder($product);

            if (!$result['eligible']) {
                continue;
            }

            $supplierId = $result['supplier']->id;

            if (!isset($bySupplier[$supplierId])) {
                $bySupplier[$supplierId] = ['supplier' => $result['supplier'], 'lines' => []];
            }

            $bySupplier[$supplierId]['lines'][] = $result['line'];
        }

        foreach ($bySupplier as $group) {
            if (empty($group['lines'])) {
                continue;
            }

            $productNames = implode(', ', array_map(fn ($line) => $line['product']->name, $group['lines']));

            try {
                $purchaseOrder = $this->createAndSendPO($group['supplier'], $group['lines']);

                if (!$purchaseOrder) {
                    Log::info("Replenishment: no PO created for ({$productNames}) - all lines were already covered by an open order from a concurrent run.");
                    continue;
                }

                if ($purchaseOrder->status === 'sent') {
                    Log::info("Replenishment: auto-ordered {$productNames} via {$purchaseOrder->po_number}, triggered by stock change.");
                } else {
                    Log::warning("Replenishment: created PO {$purchaseOrder->po_number} for {$productNames} but failed to send - left as draft.");
                }
            } catch (\Throwable $e) {
                Log::error("Replenishment: failed to auto-order ({$productNames}) for supplier #{$group['supplier']->id}: " . $e->getMessage());
            }
        }
    }
}
