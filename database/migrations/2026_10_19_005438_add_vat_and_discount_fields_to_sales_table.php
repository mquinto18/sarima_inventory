<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-line VAT/discount breakdown, following the same convention as
     * total_amount/unit_price above: one row per cart line, summed across a
     * pos_transaction_id group to get the transaction's totals (see
     * TransactionLogController::pos()).
     *
     * tax_mode/vat_type are snapshotted per line, same reasoning as
     * unit_price: if the Tax Registration setting or a product's VAT type
     * changes later, past receipts must keep showing the breakdown that was
     * actually true at the time of sale.
     *
     * discount_type/discount_id_number/discount_customer_name describe the
     * one Senior Citizen/PWD buyer for the whole transaction, so - like
     * amount_tendered/change_due - the same value is written identically to
     * every line of that transaction rather than only the first.
     *
     * amount_due is backfilled to total_amount for every existing row: no
     * sale before this migration had a discount concept, so what was charged
     * is exactly what total_amount already says.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('tax_mode')->nullable()->after('total_amount');
            $table->char('vat_type', 1)->nullable()->after('tax_mode');
            $table->decimal('vatable_sales', 10, 2)->default(0)->after('vat_type');
            $table->decimal('vat_amount', 10, 2)->default(0)->after('vatable_sales');
            $table->decimal('vat_exempt_sales', 10, 2)->default(0)->after('vat_amount');
            $table->decimal('zero_rated_sales', 10, 2)->default(0)->after('vat_exempt_sales');
            $table->string('discount_type')->nullable()->after('zero_rated_sales');
            $table->string('discount_id_number')->nullable()->after('discount_type');
            $table->string('discount_customer_name')->nullable()->after('discount_id_number');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_customer_name');
            $table->decimal('discount_vat_exempt_amount', 10, 2)->default(0)->after('discount_amount');
            $table->decimal('amount_due', 10, 2)->nullable()->after('discount_vat_exempt_amount');
        });

        DB::statement("UPDATE sales SET amount_due = total_amount, tax_mode = 'Non-VAT'");
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'tax_mode',
                'vat_type',
                'vatable_sales',
                'vat_amount',
                'vat_exempt_sales',
                'zero_rated_sales',
                'discount_type',
                'discount_id_number',
                'discount_customer_name',
                'discount_amount',
                'discount_vat_exempt_amount',
                'amount_due',
            ]);
        });
    }
};
