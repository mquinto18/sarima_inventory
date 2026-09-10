<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per delivery actually received against a purchase order line.
     *
     * purchase_order_items.quantity_received is a running counter maintained with
     * increment(), so three deliveries of 10 were indistinguishable from one of
     * 30, and purchase_orders.delivered_at only ever holds the final completion
     * time. The individual receipt events existed nowhere except as
     * stock_movements rows. This table makes them first-class.
     */
    public function up(): void
    {
        Schema::create('purchase_order_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity_received');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['purchase_order_id', 'received_at']);
        });

        $this->backfillFromStockLedger();
    }

    /**
     * Reconstruct historical receipts from stock_movements. Those rows reference
     * the PO *header* rather than the line, so the line is resolved by
     * (purchase_order_id, product_id). That is only unambiguous when the PO has a
     * single line for the product — where it is not, the row is skipped rather
     * than guessed, since attributing a delivery to the wrong line would be worse
     * than leaving it out of the backfill.
     */
    private function backfillFromStockLedger(): void
    {
        $movements = DB::table('stock_movements')
            ->where('reference_type', 'App\\Models\\PurchaseOrder')
            ->whereNotNull('reference_id')
            ->where('quantity_change', '>', 0)
            ->orderBy('created_at')
            ->get();

        foreach ($movements as $m) {
            $candidates = DB::table('purchase_order_items')
                ->where('purchase_order_id', $m->reference_id)
                ->where('product_id', $m->product_id)
                ->pluck('id');

            if ($candidates->count() !== 1) {
                continue;
            }

            DB::table('purchase_order_receipts')->insert([
                'purchase_order_id' => $m->reference_id,
                'purchase_order_item_id' => $candidates->first(),
                'product_id' => $m->product_id,
                'quantity_received' => $m->quantity_change,
                'received_by' => $m->user_id,
                'received_at' => $m->created_at,
                'notes' => 'Backfilled from stock ledger',
                'created_at' => $m->created_at,
                'updated_at' => $m->created_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_receipts');
    }
};
