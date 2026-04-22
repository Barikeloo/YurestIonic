<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('z_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('cash_session_id');
            $table->integer('report_number');
            $table->string('report_hash');
            $table->unsignedBigInteger('total_sales_cents')->default(0);
            $table->unsignedBigInteger('total_cash_cents')->default(0);
            $table->unsignedBigInteger('total_card_cents')->default(0);
            $table->unsignedBigInteger('total_other_cents')->default(0);
            $table->unsignedBigInteger('cash_in_cents')->default(0);
            $table->unsignedBigInteger('cash_out_cents')->default(0);
            $table->unsignedBigInteger('tips_cents')->default(0);
            $table->bigInteger('discrepancy_cents')->default(0);
            $table->integer('sales_count')->default(0);
            $table->integer('cancelled_sales_count')->default(0);
            $table->timestamp('generated_at');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['restaurant_id', 'id']);
            $table->unique(['restaurant_id', 'report_number']);

            $table->foreign(['restaurant_id', 'cash_session_id'], 'z_reports_restaurant_session_fk')
                ->references(['restaurant_id', 'id'])
                ->on('cash_sessions')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('z_reports');
    }
};
