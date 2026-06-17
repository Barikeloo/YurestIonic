<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printer_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('restaurant_id');
            $table->string('name', 100);
            $table->string('ip', 45);
            $table->unsignedSmallInteger('port')->default(9100);
            $table->unsignedTinyInteger('paper_width')->default(80)->comment('58 or 80 mm');
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
            $table->index(['restaurant_id', 'enabled']);
            $table->index(['restaurant_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printer_configs');
    }
};
