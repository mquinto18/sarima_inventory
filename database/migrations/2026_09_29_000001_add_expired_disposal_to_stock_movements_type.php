<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('sale', 'pos_sale', 'purchase_receipt', 'adjustment', 'initial', 'expired_disposal')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('sale', 'pos_sale', 'purchase_receipt', 'adjustment', 'initial')");
    }
};
