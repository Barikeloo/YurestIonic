<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);

            $table->unsignedSmallInteger('min_choices')->default(1);
            $table->unsignedSmallInteger('max_choices')->default(1);
            $table->timestamps();

            $table->index(['menu_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_sections');
    }
};
