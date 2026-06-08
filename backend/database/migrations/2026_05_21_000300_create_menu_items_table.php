<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('menu_sections')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained('products');

            $table->foreignId('variant_id')->nullable()->constrained('product_variants');

            $table->unsignedInteger('extra_price')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['section_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
