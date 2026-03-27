<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addParentCompositeIndexes();
        $this->addMissingColumns();
        $this->replaceSimpleForeignKeysWithCompositeOnes();
    }

    public function down(): void
    {
        $this->dropCompositeForeignKeys();
        $this->dropParentCompositeIndexes();
        $this->dropAddedColumns();
        $this->restoreSimpleForeignKeys();
    }

    private function addParentCompositeIndexes(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->unique(['restaurant_id', 'id'], 'families_restaurant_id_id_uk');
        });

        Schema::table('taxes', function (Blueprint $table) {
            $table->unique(['restaurant_id', 'id'], 'taxes_restaurant_id_id_uk');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique(['restaurant_id', 'id'], 'products_restaurant_id_id_uk');
        });

        Schema::table('zones', function (Blueprint $table) {
            $table->unique(['restaurant_id', 'id'], 'zones_restaurant_id_id_uk');
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->unique(['restaurant_id', 'id'], 'tables_restaurant_id_id_uk');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['restaurant_id', 'id'], 'users_restaurant_id_id_uk');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unique(['restaurant_id', 'id'], 'orders_restaurant_id_id_uk');
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->unique(['restaurant_id', 'id'], 'order_lines_restaurant_id_id_uk');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unique(['restaurant_id', 'id'], 'sales_restaurant_id_id_uk');
        });

        Schema::table('sales_lines', function (Blueprint $table) {
            $table->unique(['restaurant_id', 'id'], 'sales_lines_restaurant_id_id_uk');
        });
    }

    private function dropParentCompositeIndexes(): void
    {
        Schema::table('sales_lines', function (Blueprint $table) {
            $table->dropUnique('sales_lines_restaurant_id_id_uk');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_restaurant_id_id_uk');
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropUnique('order_lines_restaurant_id_id_uk');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_restaurant_id_id_uk');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_restaurant_id_id_uk');
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->dropUnique('tables_restaurant_id_id_uk');
        });

        Schema::table('zones', function (Blueprint $table) {
            $table->dropUnique('zones_restaurant_id_id_uk');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_restaurant_id_id_uk');
        });

        Schema::table('taxes', function (Blueprint $table) {
            $table->dropUnique('taxes_restaurant_id_id_uk');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->dropUnique('families_restaurant_id_id_uk');
        });
    }

    private function addMissingColumns(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('table_id')->nullable()->after('restaurant_id');
            $table->foreignId('opened_by_user_id')->nullable()->after('table_id');
            $table->foreignId('closed_by_user_id')->nullable()->after('opened_by_user_id');
        });

        Schema::table('sales_lines', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('order_line_id');
        });
    }

    private function dropAddedColumns(): void
    {
        Schema::table('sales_lines', function (Blueprint $table) {
            $table->dropColumn('product_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('closed_by_user_id');
            $table->dropColumn('opened_by_user_id');
            $table->dropColumn('table_id');
        });
    }

    private function replaceSimpleForeignKeysWithCompositeOnes(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->dropForeign(['tax_id']);

            $table->foreign(['restaurant_id', 'family_id'], 'products_restaurant_family_fk')
                ->references(['restaurant_id', 'id'])
                ->on('families')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'tax_id'], 'products_restaurant_tax_fk')
                ->references(['restaurant_id', 'id'])
                ->on('taxes')
                ->cascadeOnDelete();
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);

            $table->foreign(['restaurant_id', 'zone_id'], 'tables_restaurant_zone_fk')
                ->references(['restaurant_id', 'id'])
                ->on('zones')
                ->cascadeOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
            $table->dropForeign(['opened_by_user_id']);
            $table->dropForeign(['closed_by_user_id']);

            $table->foreign(['restaurant_id', 'table_id'], 'orders_restaurant_table_fk')
                ->references(['restaurant_id', 'id'])
                ->on('tables')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'opened_by_user_id'], 'orders_restaurant_opened_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'closed_by_user_id'], 'orders_restaurant_closed_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['user_id']);

            $table->foreign(['restaurant_id', 'order_id'], 'order_lines_restaurant_order_fk')
                ->references(['restaurant_id', 'id'])
                ->on('orders')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'product_id'], 'order_lines_restaurant_product_fk')
                ->references(['restaurant_id', 'id'])
                ->on('products')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'user_id'], 'order_lines_restaurant_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['user_id']);

            $table->foreign(['restaurant_id', 'order_id'], 'sales_restaurant_order_fk')
                ->references(['restaurant_id', 'id'])
                ->on('orders')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'user_id'], 'sales_restaurant_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'table_id'], 'sales_restaurant_table_fk')
                ->references(['restaurant_id', 'id'])
                ->on('tables')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'opened_by_user_id'], 'sales_restaurant_opened_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'closed_by_user_id'], 'sales_restaurant_closed_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('sales_lines', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropForeign(['order_line_id']);
            $table->dropForeign(['user_id']);

            $table->foreign(['restaurant_id', 'sale_id'], 'sales_lines_restaurant_sale_fk')
                ->references(['restaurant_id', 'id'])
                ->on('sales')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'order_line_id'], 'sales_lines_restaurant_order_line_fk')
                ->references(['restaurant_id', 'id'])
                ->on('order_lines')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'user_id'], 'sales_lines_restaurant_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'product_id'], 'sales_lines_restaurant_product_fk')
                ->references(['restaurant_id', 'id'])
                ->on('products')
                ->cascadeOnDelete();
        });
    }

    private function dropCompositeForeignKeys(): void
    {
        Schema::table('sales_lines', function (Blueprint $table) {
            $table->dropForeign('sales_lines_restaurant_product_fk');
            $table->dropForeign('sales_lines_restaurant_user_fk');
            $table->dropForeign('sales_lines_restaurant_order_line_fk');
            $table->dropForeign('sales_lines_restaurant_sale_fk');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign('sales_restaurant_closed_user_fk');
            $table->dropForeign('sales_restaurant_opened_user_fk');
            $table->dropForeign('sales_restaurant_table_fk');
            $table->dropForeign('sales_restaurant_user_fk');
            $table->dropForeign('sales_restaurant_order_fk');
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropForeign('order_lines_restaurant_user_fk');
            $table->dropForeign('order_lines_restaurant_product_fk');
            $table->dropForeign('order_lines_restaurant_order_fk');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_restaurant_closed_user_fk');
            $table->dropForeign('orders_restaurant_opened_user_fk');
            $table->dropForeign('orders_restaurant_table_fk');
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->dropForeign('tables_restaurant_zone_fk');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_restaurant_tax_fk');
            $table->dropForeign('products_restaurant_family_fk');
        });
    }

    private function restoreSimpleForeignKeys(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('family_id')->references('id')->on('families');
            $table->foreign('tax_id')->references('id')->on('taxes');
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->foreign('zone_id')->references('id')->on('zones');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('table_id')->references('id')->on('tables');
            $table->foreign('opened_by_user_id')->references('id')->on('users');
            $table->foreign('closed_by_user_id')->references('id')->on('users');
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::table('sales_lines', function (Blueprint $table) {
            $table->foreign('sale_id')->references('id')->on('sales');
            $table->foreign('order_line_id')->references('id')->on('order_lines');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
