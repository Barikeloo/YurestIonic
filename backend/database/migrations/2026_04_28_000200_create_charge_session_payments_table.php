<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_session_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('charge_session_id');

            $table->integer('diner_number');

            $table->integer('amount_cents');
            $table->string('payment_method', 20);

            $table->string('status', 20)->default('completed');

            $table->timestamps();

            $table->index('charge_session_id');
            $table->unique(['charge_session_id', 'diner_number', 'status'], 'unique_diner_payment');

            $table->foreign('charge_session_id')
                ->references('id')
                ->on('charge_sessions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_session_payments');
    }
};
