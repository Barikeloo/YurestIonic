<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            $table->uuid('order_id');
            $table->uuid('opened_by_user_id');

            // Datos de la sesión
            $table->integer('diners_count');
            $table->integer('total_cents');
            $table->integer('amount_per_diner');
            $table->integer('paid_diners_count')->default(0);

            // Estado
            $table->string('status', 20)->default('active'); // active, completed, cancelled

            // Cancelación
            $table->uuid('cancelled_by_user_id')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index('restaurant_id');
            $table->index('order_id');
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_sessions');
    }
};
