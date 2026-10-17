<?php

namespace App\Services;

use App\Exceptions\BatchLedgerDriftException;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Decrease stock, depleting batches in FEFO order (soonest expiry
     * first). Throws InsufficientStockException instead of allowing stock
     * to go negative. $excludeExpired should be true for anything that
     * actually sells the product (POS) so an already-expired batch can
     * never be sold even if it happens to be FEFO-earliest; other callers
     * (manual stock corrections) leave it false.
     */
    public function deductStock(Product $product, int $quantity, string $type, ?Model $reference = null, ?int $userId = null, ?string $notes = null, bool $excludeExpired = false): Product
    {
        return $this->depleteFefo($product, $quantity, $type, $reference, $userId, $notes, $excludeExpired);
    }

    /**
     * Increase stock by creating a new batch (purchase receipts, manual
     * adjustments, initial stock on product creation) rather than merging
     * into an existing one - this is what keeps old and new stock
     * distinguishable for FEFO instead of silently overwriting expiry.
     */
    public function addStock(Product $product, int $quantity, string $type, ?Model $reference = null, ?int $userId = null, ?string $notes = null, ?string $expiryDate = null, ?int $purchaseOrderItemId = null, string $batchSource = 'adjustment'): Product
    {
        return $this->addNewBatch($product, $quantity, $type, $reference, $userId, $notes, $expiryDate, $purchaseOrderItemId, $batchSource);
    }

    /**
     * Thin positive/negative dispatcher for the one caller that edits stock
     * as a signed delta (ProductController::update()'s manual form edit).
     * $newBatchExpiryDate only applies when $delta is positive - a decrease
     * has no new batch to attach an expiry to.
     */
    public function adjustStock(Product $product, int $delta, string $type, ?Model $reference = null, ?int $userId = null, ?string $notes = null, ?string $newBatchExpiryDate = null): Product
    {
        if ($delta === 0) {
            return $product;
        }

        return $delta > 0
            ? $this->addNewBatch($product, $delta, $type, $reference, $userId, $notes, $newBatchExpiryDate, null, 'adjustment')
            : $this->depleteFefo($product, abs($delta), $type, $reference, $userId, $notes, false);
    }

    /**
     * Writes off a specific, already-identified expired batch (used by
     * DisposeExpiredStock). Unlike plain FEFO deductStock(), this re-locks
     * and re-validates THIS batch specifically inside its own transaction -
     * closing a race where a POS sale could drain this exact batch between
     * the scheduled command's initial query and this transaction acquiring
     * the lock, which would otherwise cause FEFO to wrongly write off a
     * different, still-valid batch as "expired".
     */
    public function disposeBatch(ProductBatch $batch, int $quantity, ?int $userId, ?string $notes): Product
    {
        $locked = DB::transaction(function () use ($batch, $quantity, $userId, $notes) {
            $product = Product::where('id', $batch->product_id)->lockForUpdate()->first();
            $lockedBatch = ProductBatch::where('id', $batch->id)->lockForUpdate()->first();

            if (!$lockedBatch->expiry_date || $lockedBatch->expiry_date->isAfter(now()->subDay())) {
                // No longer eligible by the time we got the lock - nothing to do.
                return $product;
            }

            $take = min($quantity, $lockedBatch->quantity_remaining);

            if ($take <= 0) {
                return $product;
            }

            $stockBefore = $product->stock;
            $stockAfter = $stockBefore - $take;

            $lockedBatch->quantity_remaining -= $take;
            $lockedBatch->save();

            $product->stock = $stockAfter;
            $product->expiry_date = $this->recalculateSoonestExpiry($product);
            $product->status = Product::statusForStock($stockAfter);
            $product->save();

            StockMovement::create([
                'product_id' => $product->id,
                'product_batch_id' => $lockedBatch->id,
                'type' => 'expired_disposal',
                'quantity_change' => -$take,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'user_id' => $userId,
                'notes' => $notes,
            ]);

            return $product;
        });

        try {
            app(ReplenishmentService::class)->queueProduct($locked);
        } catch (\Throwable $e) {
            Log::error("InventoryService::disposeBatch: replenishment check failed for product #{$locked->id} ({$locked->name}): " . $e->getMessage());
        }

        return $locked;
    }

    private function addNewBatch(Product $product, int $quantity, string $type, ?Model $reference, ?int $userId, ?string $notes, ?string $expiryDate, ?int $purchaseOrderItemId, string $batchSource): Product
    {
        return DB::transaction(function () use ($product, $quantity, $type, $reference, $userId, $notes, $expiryDate, $purchaseOrderItemId, $batchSource) {
            $locked = Product::where('id', $product->id)->lockForUpdate()->first();

            $stockBefore = $locked->stock;
            $stockAfter = $stockBefore + $quantity;

            $batch = ProductBatch::create([
                'product_id' => $locked->id,
                'purchase_order_item_id' => $purchaseOrderItemId,
                'quantity_received' => $quantity,
                'quantity_remaining' => $quantity,
                'expiry_date' => $expiryDate,
                'received_at' => now(),
                'source' => $batchSource,
            ]);

            $locked->stock = $stockAfter;
            $locked->expiry_date = $this->recalculateSoonestExpiry($locked);
            $locked->status = Product::statusForStock($stockAfter);
            $locked->save();

            StockMovement::create([
                'product_id' => $locked->id,
                'product_batch_id' => $batch->id,
                'type' => $type,
                'quantity_change' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'user_id' => $userId,
                'notes' => $notes,
            ]);

            return $locked;
        });
    }

    private function depleteFefo(Product $product, int $quantity, string $type, ?Model $reference, ?int $userId, ?string $notes, bool $excludeExpired): Product
    {
        $locked = DB::transaction(function () use ($product, $quantity, $type, $reference, $userId, $notes, $excludeExpired) {
            $locked = Product::where('id', $product->id)->lockForUpdate()->first();
            $stockBefore = $locked->stock;

            if ($quantity > $stockBefore) {
                throw new InsufficientStockException($stockBefore, $quantity, $locked->id);
            }

            $query = ProductBatch::where('product_id', $locked->id)
                ->available()
                ->fefoOrder();

            if ($excludeExpired) {
                $query->where(function ($q) {
                    $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', now()->toDateString());
                });
            }

            $batches = $query->lockForUpdate()->get();

            $remaining = $quantity;
            $running = $stockBefore;

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($batch->quantity_remaining, $remaining);
                $batch->quantity_remaining -= $take;
                $batch->save();

                StockMovement::create([
                    'product_id' => $locked->id,
                    'product_batch_id' => $batch->id,
                    'type' => $type,
                    'quantity_change' => -$take,
                    'stock_before' => $running,
                    'stock_after' => $running - $take,
                    'reference_type' => $reference ? get_class($reference) : null,
                    'reference_id' => $reference?->id,
                    'user_id' => $userId,
                    'notes' => $notes,
                ]);

                $running -= $take;
                $remaining -= $take;
            }

            if ($remaining > 0) {
                // products.stock said this much was available, but the batch
                // ledger (possibly filtered to non-expired only) couldn't
                // actually cover it. A real cache/ledger bug, not a normal
                // "not enough stock" case.
                throw new BatchLedgerDriftException($locked->id, $quantity, $quantity - $remaining);
            }

            $locked->stock = $running;
            $locked->expiry_date = $this->recalculateSoonestExpiry($locked);
            $locked->status = Product::statusForStock($running);
            $locked->save();

            return $locked;
        });

        // Queuing (not checking immediately) lets ReplenishmentService batch
        // every product touched by this request into one PO/email per
        // supplier instead of one per product - see queueProduct(). It
        // internally defers the actual PDF/email send until the outermost
        // transaction commits, so a mail/PO failure can never undo a
        // sale/adjustment/disposal that already committed, and a supplier
        // never gets an email for a PO that got rolled back.
        try {
            app(ReplenishmentService::class)->queueProduct($locked);
        } catch (\Throwable $e) {
            Log::error("InventoryService: replenishment check failed for product #{$locked->id} ({$locked->name}): " . $e->getMessage());
        }

        return $locked;
    }

    /**
     * Clears expiry on every one of this product's batches - used when a
     * product's category changes to one whose items never expire (e.g.
     * Medical Supplies). Only the expiry dimension changes; quantities are
     * untouched.
     */
    public function clearAllBatchExpiry(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $locked = Product::where('id', $product->id)->lockForUpdate()->first();

            ProductBatch::where('product_id', $locked->id)->update(['expiry_date' => null]);

            $locked->expiry_date = null;
            $locked->save();

            return $locked;
        });
    }

    /**
     * Soonest expiry among this product's batches that still have stock
     * left - the cached products.expiry_date is kept equal to this after
     * every mutation.
     */
    private function recalculateSoonestExpiry(Product $product): ?Carbon
    {
        $min = ProductBatch::where('product_id', $product->id)
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->min('expiry_date');

        return $min ? Carbon::parse($min) : null;
    }
}
