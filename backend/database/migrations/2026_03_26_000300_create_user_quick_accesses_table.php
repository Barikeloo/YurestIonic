<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_quick_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id', 100);
            $table->timestamp('last_login_at');
            $table->timestamps();

            $table->unique(['restaurant_id', 'user_id', 'device_id'], 'uq_user_quick_accesses_rest_user_device');
            $table->index(['device_id', 'last_login_at'], 'idx_user_quick_accesses_device_last_login');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_quick_accesses');
    }
};
