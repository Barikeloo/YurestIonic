<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('device_id', 100);
            $table->foreignId('opened_by_user_id');
            $table->foreignId('closed_by_user_id')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('initial_amount_cents');
            $table->integer('final_amount_cents')->nullable();
            $table->integer('expected_amount_cents')->nullable();
            $table->integer('discrepancy_cents')->nullable();
            $table->string('discrepancy_reason')->nullable();
            $table->unsignedBigInteger('z_report_number')->nullable();
            $table->string('z_report_hash', 64)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['open', 'closing', 'closed', 'abandoned'])->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['restaurant_id', 'id'], 'cash_sessions_restaurant_id_id_uk');
            $table->index(['restaurant_id', 'device_id', 'status'], 'cash_sessions_restaurant_device_status_idx');
            $table->unique(['restaurant_id', 'z_report_number'], 'cash_sessions_restaurant_z_number_uk');

            $table->foreign(['restaurant_id', 'opened_by_user_id'], 'cash_sessions_restaurant_opened_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'closed_by_user_id'], 'cash_sessions_restaurant_closed_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
