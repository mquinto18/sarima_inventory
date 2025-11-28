<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('category')->nullable();
            $table->integer('stock')->default(0);
            $table->string('status')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('reorder_level')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name', 'category', 'stock', 'status', 'price', 'reorder_level']);
        });
    }
};
