<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tips', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable();
            $table->foreignId('cash_session_id');
            $table->unsignedInteger('amount_cents');
            $table->enum('source', ['card_added', 'cash_declared']);
            $table->foreignId('beneficiary_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['restaurant_id', 'id'], 'tips_restaurant_id_id_uk');
            $table->index(['restaurant_id', 'cash_session_id', 'source'], 'tips_restaurant_session_source_idx');

            $table->foreign(['restaurant_id', 'sale_id'], 'tips_restaurant_sale_fk')
                ->references(['restaurant_id', 'id'])
                ->on('sales')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'cash_session_id'], 'tips_restaurant_session_fk')
                ->references(['restaurant_id', 'id'])
                ->on('cash_sessions')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'beneficiary_user_id'], 'tips_restaurant_beneficiary_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tips');
    }
};
