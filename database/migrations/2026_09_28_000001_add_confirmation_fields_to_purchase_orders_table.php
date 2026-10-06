<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('confirmation_token')->nullable()->unique()->after('pdf_path');
            $table->timestamp('confirmed_at')->nullable()->after('sent_at');
            $table->text('confirmation_note')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['confirmation_token', 'confirmed_at', 'confirmation_note']);
        });
    }
};
