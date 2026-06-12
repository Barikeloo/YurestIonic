<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('report_type', 32);
            $table->string('format', 8);
            $table->string('frequency', 16);
            $table->string('time', 5);
            $table->tinyInteger('weekday')->nullable();
            $table->tinyInteger('day_of_month')->nullable();
            $table->json('recipients');
            $table->string('name');
            $table->boolean('active')->default(true);
            // dateTime (not timestamp) so inactive reports can park next_run_at
            // at the far-future sentinel 9999-12-31, beyond the MySQL TIMESTAMP
            // 2038 limit.
            $table->dateTime('last_run_at')->nullable();
            $table->dateTime('next_run_at')->index();
            $table->uuid('created_by_user_uuid')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['restaurant_id', 'id'], 'scheduled_reports_restaurant_id_id_uk');
            $table->index(['active', 'next_run_at'], 'scheduled_reports_active_next_run_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
