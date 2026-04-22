<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('sale_id');
            $table->foreignId('cash_session_id');
            $table->enum('method', ['cash', 'card', 'bizum', 'voucher', 'invitation', 'other']);
            $table->integer('amount_cents');
            $table->json('metadata')->nullable();
            $table->foreignId('user_id');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['restaurant_id', 'id'], 'sale_payments_restaurant_id_id_uk');
            $table->index(['restaurant_id', 'sale_id'], 'sale_payments_restaurant_sale_idx');
            $table->index(['restaurant_id', 'cash_session_id', 'method'], 'sale_payments_restaurant_session_method_idx');

            $table->foreign(['restaurant_id', 'sale_id'], 'sale_payments_restaurant_sale_fk')
                ->references(['restaurant_id', 'id'])
                ->on('sales')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'cash_session_id'], 'sale_payments_restaurant_session_fk')
                ->references(['restaurant_id', 'id'])
                ->on('cash_sessions')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'user_id'], 'sale_payments_restaurant_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
