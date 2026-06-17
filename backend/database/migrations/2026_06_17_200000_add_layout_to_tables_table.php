<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table): void {
            $table->integer('pos_x')->nullable()->default(null)->after('name');
            $table->integer('pos_y')->nullable()->default(null)->after('pos_x');
            $table->integer('width')->nullable()->default(null)->after('pos_y');
            $table->integer('height')->nullable()->default(null)->after('width');
            $table->string('shape', 6)->default('rect')->after('height');
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table): void {
            $table->dropColumn(['pos_x', 'pos_y', 'width', 'height', 'shape']);
        });
    }
};
