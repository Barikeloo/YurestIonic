<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de órdenes/pedidos
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->enum('status', ['open', 'to-charge', 'cancelled', 'invoiced'])->default('open');
            $table->foreignId('table_id')->constrained('tables');
            $table->foreignId('opened_by_user_id')->constrained('users');
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users');
            $table->unsignedInteger('diners');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de líneas de órdenes
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('price');
            $table->unsignedInteger('tax_percentage');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
