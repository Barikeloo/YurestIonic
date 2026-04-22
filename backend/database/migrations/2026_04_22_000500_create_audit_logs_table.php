<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('entity_type', 64);
            $table->string('entity_id', 36);
            $table->string('action', 64);
            $table->foreignId('user_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['restaurant_id', 'entity_type', 'entity_id'], 'audit_logs_restaurant_entity_idx');
            $table->index(['restaurant_id', 'created_at'], 'audit_logs_restaurant_created_idx');
            $table->index(['restaurant_id', 'user_id'], 'audit_logs_restaurant_user_idx');

            $table->foreign(['restaurant_id', 'user_id'], 'audit_logs_restaurant_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
