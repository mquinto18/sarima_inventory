<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Decrease stock. Throws InsufficientStockException instead of allowing
     * stock to go negative.
     */
    public function deductStock(Product $product, int $quantity, string $type, ?Model $reference = null, ?int $userId = null, ?string $notes = null): Product
    {
        return $this->adjustStock($product, -$quantity, $type, $reference, $userId, $notes);
    }

    /**
     * Increase stock (purchase receipts, manual adjustments).
     */
    public function addStock(Product $product, int $quantity, string $type, ?Model $reference = null, ?int $userId = null, ?string $notes = null): Product
    {
        return $this->adjustStock($product, $quantity, $type, $reference, $userId, $notes);
    }

    /**
     * The single place stock is ever mutated. Locks the row, guards against
     * negative stock, keeps `status` in sync, and writes a stock_movements
     * ledger row for every change.
     */
    public function adjustStock(Product $product, int $delta, string $type, ?Model $reference = null, ?int $userId = null, ?string $notes = null): Product
    {
        $locked = DB::transaction(function () use ($product, $delta, $type, $reference, $userId, $notes) {
            $locked = Product::where('id', $product->id)->lockForUpdate()->first();

            $stockBefore = $locked->stock;
            $stockAfter = $stockBefore + $delta;

            if ($stockAfter < 0) {
                throw new InsufficientStockException($stockBefore, abs($delta), $locked->id);
            }

            $locked->stock = $stockAfter;
            $locked->status = Product::statusForStock($stockAfter);
            $locked->save();

            StockMovement::create([
                'product_id' => $locked->id,
                'type' => $type,
                'quantity_change' => $delta,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'user_id' => $userId,
                'notes' => $notes,
            ]);

            return $locked;
        });

        // Queuing (not checking immediately) lets ReplenishmentService batch
        // every product touched by this request into one PO/email per
        // supplier instead of one per product - see queueProduct(). It
        // internally defers the actual PDF/email send until the outermost
        // transaction commits (every caller of adjustStock() wraps it in
        // its own DB::transaction()), so a mail/PO failure can never undo a
        // sale/adjustment/disposal that already committed, and a supplier
        // never gets an email for a PO that got rolled back. Only decreases
        // can push a product to/below its reorder point.
        if ($delta < 0) {
            try {
                app(ReplenishmentService::class)->queueProduct($locked);
            } catch (\Throwable $e) {
                Log::error("InventoryService: replenishment check failed for product #{$locked->id} ({$locked->name}): " . $e->getMessage());
            }
        }

        return $locked;
    }
}
