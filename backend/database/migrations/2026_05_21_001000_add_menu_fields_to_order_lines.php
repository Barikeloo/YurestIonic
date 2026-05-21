<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que una order_line represente un menú completo en lugar de un único
 * producto: product_id pasa a ser opcional y se añaden los campos para enlazar
 * el menú y guardar las elecciones del comensal (sección, producto, variante,
 * extras y suplemento del menú).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop FK compuesto que apunta a product_id (lo recrearemos como nullable).
        Schema::table('order_lines', function (Blueprint $table): void {
            $table->dropForeign('order_lines_restaurant_product_fk');
        });

        // MySQL no permite cambiar nullability con Blueprint sin DBAL; usamos raw.
        DB::statement('ALTER TABLE order_lines MODIFY product_id BIGINT UNSIGNED NULL');

        Schema::table('order_lines', function (Blueprint $table): void {
            $table->string('menu_id', 36)->nullable()->after('product_id');
            $table->string('menu_name')->nullable()->after('menu_id');
            $table->json('menu_selections')->nullable()->after('menu_name');

            // FK compuesto restaurado, ahora tolera product_id NULL.
            $table->foreign(['restaurant_id', 'product_id'], 'order_lines_restaurant_product_fk')
                ->references(['restaurant_id', 'id'])
                ->on('products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table): void {
            $table->dropForeign('order_lines_restaurant_product_fk');
            $table->dropColumn(['menu_id', 'menu_name', 'menu_selections']);
        });

        DB::statement('ALTER TABLE order_lines MODIFY product_id BIGINT UNSIGNED NOT NULL');

        Schema::table('order_lines', function (Blueprint $table): void {
            $table->foreign(['restaurant_id', 'product_id'], 'order_lines_restaurant_product_fk')
                ->references(['restaurant_id', 'id'])
                ->on('products')
                ->cascadeOnDelete();
        });
    }
};
