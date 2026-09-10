<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('pos_transaction_id')->nullable()->after('notes');
            $table->index('pos_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['pos_transaction_id']);
            $table->dropColumn('pos_transaction_id');
        });
    }
};
