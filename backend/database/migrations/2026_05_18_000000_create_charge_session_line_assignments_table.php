<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_session_line_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('charge_session_id');
            $table->uuid('order_line_id');
            $table->unsignedSmallInteger('diner_number');
            $table->timestamps();

            $table->unique(['charge_session_id', 'order_line_id'], 'unique_session_line_assignment');
            $table->index(['charge_session_id', 'diner_number'], 'session_diner_idx');

            $table->foreign('charge_session_id')
                ->references('id')
                ->on('charge_sessions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_session_line_assignments');
    }
};
