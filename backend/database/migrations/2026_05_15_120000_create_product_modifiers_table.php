<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name', 255);
            $table->enum('type', ['extra', 'accompaniment'])->default('extra');
            $table->boolean('is_required')->default(false);
            $table->enum('selection_type', ['single', 'multi'])->default('multi');
            $table->integer('price')->unsigned()->default(0);
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->unsigned()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_modifiers');
    }
};
