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
            $table->unsignedInteger('price'); // céntimos
            $table->boolean('active')->default(true);
            // Validez (opcional): si ambos null, el menú es permanente
            $table->date('validity_from')->nullable();
            $table->date('validity_to')->nullable();
            // Disponibilidad semanal (bitmask 7 bits, bit 0 = Lunes ... bit 6 = Domingo)
            // 127 (0b1111111) = todos los días
            $table->unsignedTinyInteger('available_days')->default(127);
            // Franja horaria opcional (si null/null, todo el día)
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
