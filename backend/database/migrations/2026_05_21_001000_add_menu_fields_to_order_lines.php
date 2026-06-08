<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('order_lines', function (Blueprint $table): void {
                $table->dropForeign('order_lines_restaurant_product_fk');
            });
        } catch (\RuntimeException) {
        }

        try {
            DB::statement('ALTER TABLE order_lines MODIFY product_id BIGINT UNSIGNED NULL');
        } catch (\RuntimeException) {
        }

        try {
            Schema::table('order_lines', function (Blueprint $table): void {
                $table->string('menu_id', 36)->nullable()->after('product_id');
                $table->string('menu_name')->nullable()->after('menu_id');
                $table->json('menu_selections')->nullable()->after('menu_name');

                $table->foreign(['restaurant_id', 'product_id'], 'order_lines_restaurant_product_fk')
                    ->references(['restaurant_id', 'id'])
                    ->on('products')
                    ->cascadeOnDelete();
            });
        } catch (\RuntimeException) {
        }
    }

    public function down(): void
    {
        try {
            Schema::table('order_lines', function (Blueprint $table): void {
                $table->dropForeign('order_lines_restaurant_product_fk');
                $table->dropColumn(['menu_id', 'menu_name', 'menu_selections']);
            });
        } catch (\RuntimeException) {
        }

        try {
            DB::statement('ALTER TABLE order_lines MODIFY product_id BIGINT UNSIGNED NOT NULL');
        } catch (\RuntimeException) {
        }

        try {
            Schema::table('order_lines', function (Blueprint $table): void {
                $table->foreign(['restaurant_id', 'product_id'], 'order_lines_restaurant_product_fk')
                    ->references(['restaurant_id', 'id'])
                    ->on('products')
                    ->cascadeOnDelete();
            });
        } catch (\Illuminate\Database\QueryException) {
        }
    }
};
