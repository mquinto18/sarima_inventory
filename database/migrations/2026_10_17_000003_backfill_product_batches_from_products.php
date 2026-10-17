<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time data migration: converts every product's existing scalar
 * stock/expiry_date into a single starting batch, so nothing is lost when
 * batch-level tracking is introduced. Products with stock = 0 get no batch
 * (nothing to track). Must run before any application code that assumes
 * products.stock is backed by product_batches rows.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('products')->where('stock', '>', 0)->orderBy('id')
            ->chunk(200, function ($products) {
                $now = now();

                foreach ($products as $product) {
                    DB::table('product_batches')->insert([
                        'product_id' => $product->id,
                        'purchase_order_item_id' => null,
                        'quantity_received' => $product->stock,
                        'quantity_remaining' => $product->stock,
                        'expiry_date' => $product->expiry_date,
                        'received_at' => $product->updated_at ?? $product->created_at ?? $now,
                        'source' => 'legacy_backfill',
                        'notes' => 'Backfilled from products.stock/expiry_date when batch tracking was introduced.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('product_batches')->where('source', 'legacy_backfill')->delete();
    }
};
