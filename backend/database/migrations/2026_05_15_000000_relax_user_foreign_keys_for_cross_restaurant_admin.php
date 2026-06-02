<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign('orders_restaurant_opened_user_fk');
                $table->dropForeign('orders_restaurant_closed_user_fk');

                $table->foreign('opened_by_user_id', 'orders_opened_by_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('closed_by_user_id', 'orders_closed_by_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
            });

            Schema::table('order_lines', function (Blueprint $table) {
                $table->dropForeign('order_lines_restaurant_user_fk');

                $table->foreign('user_id', 'order_lines_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
            });

            Schema::table('sales', function (Blueprint $table) {
                $table->dropForeign('sales_restaurant_user_fk');
                $table->dropForeign('sales_restaurant_opened_user_fk');
                $table->dropForeign('sales_restaurant_closed_user_fk');
                $table->dropForeign('sales_restaurant_cancelled_user_fk');

                $table->foreign('user_id', 'sales_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('opened_by_user_id', 'sales_opened_by_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('closed_by_user_id', 'sales_closed_by_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('cancelled_by_user_id', 'sales_cancelled_by_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
            });

            Schema::table('sales_lines', function (Blueprint $table) {
                $table->dropForeign('sales_lines_restaurant_user_fk');

                $table->foreign('user_id', 'sales_lines_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
            });

            Schema::table('cash_sessions', function (Blueprint $table) {
                $table->dropForeign('cash_sessions_restaurant_opened_user_fk');
                $table->dropForeign('cash_sessions_restaurant_closed_user_fk');

                $table->foreign('opened_by_user_id', 'cash_sessions_opened_by_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('closed_by_user_id', 'cash_sessions_closed_by_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
            });

            Schema::table('cash_movements', function (Blueprint $table) {
                $table->dropForeign('cash_movements_restaurant_user_fk');

                $table->foreign('user_id', 'cash_movements_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
            });

            Schema::table('sale_payments', function (Blueprint $table) {
                $table->dropForeign('sale_payments_restaurant_user_fk');

                $table->foreign('user_id', 'sale_payments_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
            });

            Schema::table('tips', function (Blueprint $table) {
                $table->dropForeign('tips_restaurant_beneficiary_fk');

                $table->foreign('beneficiary_user_id', 'tips_beneficiary_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
            });

            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign('audit_logs_restaurant_user_fk');

                $table->foreign('user_id', 'audit_logs_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
            });
        } catch (\RuntimeException) {
        }
    }

    public function down(): void
    {
        try {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign('audit_logs_user_fk');

                $table->foreign(['restaurant_id', 'user_id'], 'audit_logs_restaurant_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
            });

            Schema::table('tips', function (Blueprint $table) {
                $table->dropForeign('tips_beneficiary_user_fk');

                $table->foreign(['restaurant_id', 'beneficiary_user_id'], 'tips_restaurant_beneficiary_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
            });

            Schema::table('sale_payments', function (Blueprint $table) {
                $table->dropForeign('sale_payments_user_fk');

                $table->foreign(['restaurant_id', 'user_id'], 'sale_payments_restaurant_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
            });

            Schema::table('cash_movements', function (Blueprint $table) {
                $table->dropForeign('cash_movements_user_fk');

                $table->foreign(['restaurant_id', 'user_id'], 'cash_movements_restaurant_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
            });

            Schema::table('cash_sessions', function (Blueprint $table) {
                $table->dropForeign('cash_sessions_opened_by_user_fk');
                $table->dropForeign('cash_sessions_closed_by_user_fk');

                $table->foreign(['restaurant_id', 'opened_by_user_id'], 'cash_sessions_restaurant_opened_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
                $table->foreign(['restaurant_id', 'closed_by_user_id'], 'cash_sessions_restaurant_closed_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
            });

            Schema::table('sales_lines', function (Blueprint $table) {
                $table->dropForeign('sales_lines_user_fk');

                $table->foreign(['restaurant_id', 'user_id'], 'sales_lines_restaurant_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
            });

            Schema::table('sales', function (Blueprint $table) {
                $table->dropForeign('sales_user_fk');
                $table->dropForeign('sales_opened_by_user_fk');
                $table->dropForeign('sales_closed_by_user_fk');
                $table->dropForeign('sales_cancelled_by_user_fk');

                $table->foreign(['restaurant_id', 'user_id'], 'sales_restaurant_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
                $table->foreign(['restaurant_id', 'opened_by_user_id'], 'sales_restaurant_opened_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
                $table->foreign(['restaurant_id', 'closed_by_user_id'], 'sales_restaurant_closed_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
                $table->foreign(['restaurant_id', 'cancelled_by_user_id'], 'sales_restaurant_cancelled_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
            });

            Schema::table('order_lines', function (Blueprint $table) {
                $table->dropForeign('order_lines_user_fk');

                $table->foreign(['restaurant_id', 'user_id'], 'order_lines_restaurant_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign('orders_opened_by_user_fk');
                $table->dropForeign('orders_closed_by_user_fk');

                $table->foreign(['restaurant_id', 'opened_by_user_id'], 'orders_restaurant_opened_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
                $table->foreign(['restaurant_id', 'closed_by_user_id'], 'orders_restaurant_closed_user_fk')
                    ->references(['restaurant_id', 'id'])->on('users')->cascadeOnDelete();
            });
        } catch (\RuntimeException) {
        }
    }
};
