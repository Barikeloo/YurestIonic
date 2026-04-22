<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->unsignedInteger('diner_number')->nullable()->after('tax_percentage');
            $table->unsignedInteger('discount_percent')->nullable()->after('diner_number');
            $table->unsignedInteger('discount_amount_cents')->nullable()->after('discount_percent');
            $table->string('discount_reason')->nullable()->after('discount_amount_cents');
            $table->boolean('is_invitation')->default(false)->after('discount_reason');
            $table->unsignedInteger('price_override_cents')->nullable()->after('is_invitation');
            $table->text('notes')->nullable()->after('price_override_cents');
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropColumn([
                'diner_number',
                'discount_percent',
                'discount_amount_cents',
                'discount_reason',
                'is_invitation',
                'price_override_cents',
                'notes',
            ]);
        });
    }
};
