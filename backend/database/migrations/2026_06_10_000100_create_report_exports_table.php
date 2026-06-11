<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->uuid('user_uuid')->nullable();
            $table->string('user_name');
            $table->string('report_type', 32);
            $table->string('title');
            $table->string('format', 8);
            $table->string('filename');
            $table->unsignedInteger('size_bytes')->default(0);
            $table->string('storage_path');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['restaurant_id', 'id'], 'report_exports_restaurant_id_id_uk');
            $table->index(['restaurant_id', 'created_at'], 'report_exports_restaurant_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
