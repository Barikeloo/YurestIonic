<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('from_table_id')->constrained('tables');
            $table->foreignId('to_table_id')->constrained('tables');
            $table->foreignId('transferred_by_user_id')->constrained('users');
            $table->timestamp('transferred_at');
            $table->timestamps();

            $table->index('order_id', 'order_transfers_order_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_transfers');
    }
};
