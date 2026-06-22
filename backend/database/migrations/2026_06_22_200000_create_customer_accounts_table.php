<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('email', 255);
            $table->string('password_hash', 255);
            $table->integer('points')->default(0);
            $table->bigInteger('total_spent_cents')->default(0)->unsigned();
            $table->integer('visits_count')->default(0)->unsigned();
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamps();
            $table->unique(['restaurant_id', 'email']);
        });

        Schema::create('customer_visits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->char('order_id', 36)->nullable();
            $table->char('guest_session_id', 36)->nullable();
            $table->integer('points_earned')->default(0);
            $table->bigInteger('amount_cents')->default(0)->unsigned();
            $table->timestamp('visited_at');
            $table->timestamps();
        });

        Schema::create('customer_offers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percent', 'fixed_cents', 'points_multiplier'])->default('percent');
            $table->integer('discount_value')->unsigned();
            $table->integer('min_points')->default(0)->unsigned();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_offers');
        Schema::dropIfExists('customer_visits');
        Schema::dropIfExists('customer_accounts');
    }
};
