<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('cash_session_id');
            $table->enum('type', ['in', 'out']);
            $table->enum('reason_code', [
                'change_refill',
                'supplier_payment',
                'tip_declared',
                'sangria',
                'adjustment',
                'other',
            ]);
            $table->unsignedInteger('amount_cents');
            $table->string('description')->nullable();
            $table->foreignId('user_id');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['restaurant_id', 'id'], 'cash_movements_restaurant_id_id_uk');
            $table->index(['restaurant_id', 'cash_session_id', 'created_at'], 'cash_movements_restaurant_session_idx');

            $table->foreign(['restaurant_id', 'cash_session_id'], 'cash_movements_restaurant_session_fk')
                ->references(['restaurant_id', 'id'])
                ->on('cash_sessions')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'user_id'], 'cash_movements_restaurant_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
