<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Actualizar tabla sales para agregar order_id y user_id
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('uuid')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->after('order_id')->constrained('users')->cascadeOnDelete();
        });

        // Actualizar tabla sales_lines para agregar order_line_id
        Schema::table('sales_lines', function (Blueprint $table) {
            $table->foreignId('order_line_id')->nullable()->after('sale_id')->constrained('order_lines')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_lines', function (Blueprint $table) {
            $table->dropForeignIdFor('order_lines');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeignIdFor('users');
            $table->dropForeignIdFor('orders');
        });
    }
};
