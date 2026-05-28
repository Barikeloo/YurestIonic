<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_saved_views', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name', 120);
            $table->string('icon', 40)->nullable();
            $table->json('filters');
            $table->timestamps();

            $table->index(['restaurant_id', 'user_id'], 'audit_saved_views_restaurant_user_idx');
            $table->unique(['restaurant_id', 'user_id', 'name'], 'audit_saved_views_restaurant_user_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_saved_views');
    }
};
