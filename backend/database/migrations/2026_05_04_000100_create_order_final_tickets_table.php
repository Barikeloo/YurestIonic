<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_final_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('closed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('ticket_number');
            $table->unsignedInteger('total_consumed_cents');
            $table->unsignedInteger('total_paid_cents');
            $table->json('payments_snapshot');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['restaurant_id', 'ticket_number'], 'order_final_tickets_restaurant_ticket_uk');
            $table->index(['restaurant_id', 'order_id'], 'order_final_tickets_restaurant_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_final_tickets');
    }
};
