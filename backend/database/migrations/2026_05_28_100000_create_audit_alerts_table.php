<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_alerts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('action', 64);
            $table->string('anomaly_kind', 64);
            $table->string('entity_type', 32);
            $table->string('entity_id', 36);
            $table->string('summary', 512)->nullable();
            $table->json('metadata');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('device_id', 64)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at');

            $table->index(['restaurant_id', 'read_at', 'created_at']);
            $table->index(['restaurant_id', 'anomaly_kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_alerts');
    }
};
