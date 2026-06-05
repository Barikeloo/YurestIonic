<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_chain_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->boolean('is_valid');
            $table->integer('total_events');
            $table->integer('verified_count');
            $table->json('broken_events');
            $table->integer('first_broken_index')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();

            $table->index(['restaurant_id', 'verified_at'], 'audit_chain_verif_restaurant_verified_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_chain_verifications');
    }
};
