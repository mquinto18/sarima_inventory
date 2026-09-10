<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records which cashier rang up a sale.
     *
     * Until now Auth::id() was passed to InventoryService::deductStock() and
     * landed only in stock_movements, so answering "who sold this" meant joining
     * through the stock ledger. Storing it on the sale itself makes the POS
     * transaction log a straight query.
     *
     * Nullable: most existing sales are legacy/seeded rows with no POS
     * transaction and no cashier, and a soft-deleted user must not take its
     * sales with it.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('pos_transaction_id')
                ->constrained('users')->nullOnDelete();
        });

        // Backfill real POS sales from the stock ledger, which already holds the
        // actor for every one of them.
        DB::statement("
            UPDATE sales s
            JOIN stock_movements m
              ON m.reference_type = 'App\\\\Models\\\\Sale'
             AND m.reference_id = s.id
            SET s.user_id = m.user_id
            WHERE s.pos_transaction_id IS NOT NULL
              AND m.user_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
