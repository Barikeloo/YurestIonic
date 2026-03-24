<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar restaurant_id a users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants');
            $table->string('pin')->nullable()->after('email');
        });

        // Agregar restaurant_id a families
        Schema::table('families', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants');
        });

        // Agregar restaurant_id a taxes
        Schema::table('taxes', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants');
        });

        // Agregar restaurant_id a products
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants');
        });

        // Agregar restaurant_id a zones
        Schema::table('zones', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants');
        });

        // Agregar restaurant_id a tables
        Schema::table('tables', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants');
        });

        // Agregar restaurant_id a sales
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants');
        });

        // Agregar restaurant_id a sales_lines
        Schema::table('sales_lines', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants');
        });
    }

    public function down(): void
    {
        Schema::table('sales_lines', function (Blueprint $table) {
            $table->dropForeignIdFor('restaurants');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeignIdFor('restaurants');
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->dropForeignIdFor('restaurants');
        });

        Schema::table('zones', function (Blueprint $table) {
            $table->dropForeignIdFor('restaurants');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeignIdFor('restaurants');
        });

        Schema::table('taxes', function (Blueprint $table) {
            $table->dropForeignIdFor('restaurants');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->dropForeignIdFor('restaurants');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pin');
            $table->dropForeignIdFor('restaurants');
        });
    }
};
