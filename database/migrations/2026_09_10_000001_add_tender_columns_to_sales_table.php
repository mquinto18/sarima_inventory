<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cash tendered and change are facts about a whole POS transaction, not a
     * single line. They are stored on every line of the transaction (all rows
     * sharing a pos_transaction_id) rather than in a separate table: the same
     * values are written once inside one DB transaction and never updated, so
     * they cannot diverge, and the receipt already reconstructs
     * transaction-level figures from the same group.
     *
     * Nullable because every sale recorded before this migration - and any
     * non-cash flow added later - has no tendered amount.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('amount_tendered', 10, 2)->nullable()->after('total_amount');
            $table->decimal('change_due', 10, 2)->nullable()->after('amount_tendered');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['amount_tendered', 'change_due']);
        });
    }
};
