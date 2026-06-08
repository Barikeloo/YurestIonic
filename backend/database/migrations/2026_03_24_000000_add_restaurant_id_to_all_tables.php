<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('pin')->nullable()->after('email');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants')->cascadeOnDelete();
        });

        Schema::table('taxes', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants')->cascadeOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants')->cascadeOnDelete();
        });

        Schema::table('zones', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants')->cascadeOnDelete();
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants')->cascadeOnDelete();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants')->cascadeOnDelete();
        });

        Schema::table('sales_lines', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->after('id')->constrained('restaurants')->cascadeOnDelete();
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
