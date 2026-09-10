<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($product, $delta, $type, $reference, $userId, $notes) {
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
    }
}
