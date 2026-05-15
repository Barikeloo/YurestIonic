<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->string('variant_id', 36)->nullable()->after('product_id');
            $table->string('variant_name')->nullable()->after('variant_id');
            $table->json('modifiers')->nullable()->after('variant_name');
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropColumn(['variant_id', 'variant_name', 'modifiers']);
        });
    }
};
