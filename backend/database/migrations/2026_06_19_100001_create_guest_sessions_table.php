<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('table_qr_token_id')->constrained('table_qr_tokens')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('session_token', 64)->unique();
            $table->enum('identity_mode', ['anonymous', 'named', 'registered'])->default('anonymous');
            $table->string('guest_name', 100)->nullable();
            $table->boolean('opened_table')->default(false);
            $table->unsignedSmallInteger('diners_count')->nullable();
            $table->timestamp('check_requested_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_sessions');
    }
};
