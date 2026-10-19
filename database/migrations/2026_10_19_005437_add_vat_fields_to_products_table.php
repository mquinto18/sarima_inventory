<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * vat_type: T = VATable, X = VAT-exempt, Z = zero-rated. Defaults to T
     * (the common case for pharmacy medicines) so existing products need no
     * manual tagging to keep behaving exactly as before VAT support existed.
     *
     * senior_pwd_eligible: whether this product can receive the 20%
     * Senior Citizen/PWD discount at checkout. Defaults to true - nearly
     * every product here is a medicine, which RA 9994/10754 cover.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->char('vat_type', 1)->default('T')->after('price');
            $table->boolean('senior_pwd_eligible')->default(true)->after('vat_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['vat_type', 'senior_pwd_eligible']);
        });
    }
};
