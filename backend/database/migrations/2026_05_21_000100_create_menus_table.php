<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('tax_id')->constrained('taxes');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price');

            $table->boolean('active')->default(true);

            $table->date('validity_from')->nullable();
            $table->date('validity_to')->nullable();

            $table->unsignedTinyInteger('available_days')->default(127);

            $table->time('available_from_time')->nullable();
            $table->time('available_to_time')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['restaurant_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
