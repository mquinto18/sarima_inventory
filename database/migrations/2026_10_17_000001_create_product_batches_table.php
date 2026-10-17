<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity_received');
            $table->unsignedInteger('quantity_remaining');
            // Null = untracked/indefinite shelf life, not "unknown" - mirrors
            // products.expiry_date's existing nullable semantics.
            $table->date('expiry_date')->nullable();
            $table->timestamp('received_at');
            $table->string('source'); // purchase_receipt | initial | adjustment | legacy_backfill
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'expiry_date']);
            $table->index(['product_id', 'quantity_remaining']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
