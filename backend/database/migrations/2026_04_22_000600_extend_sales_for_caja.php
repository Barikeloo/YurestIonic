<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('cash_session_id')->nullable()->after('closed_by_user_id');
            $table->enum('status', ['closed', 'cancelled', 'refunded'])->default('closed')->after('ticket_number');
            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->foreignId('cancelled_by_user_id')->nullable()->after('cancelled_at');
            $table->string('cancel_reason')->nullable()->after('cancelled_by_user_id');
            $table->foreignId('parent_sale_id')->nullable()->after('cancel_reason');
            $table->enum('document_type', ['simplified', 'full_invoice'])->default('simplified')->after('parent_sale_id');
            $table->json('customer_fiscal_data')->nullable()->after('document_type');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreign(['restaurant_id', 'cash_session_id'], 'sales_restaurant_cash_session_fk')
                ->references(['restaurant_id', 'id'])
                ->on('cash_sessions')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'cancelled_by_user_id'], 'sales_restaurant_cancelled_user_fk')
                ->references(['restaurant_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign(['restaurant_id', 'parent_sale_id'], 'sales_restaurant_parent_sale_fk')
                ->references(['restaurant_id', 'id'])
                ->on('sales')
                ->cascadeOnDelete();

            $table->index(['restaurant_id', 'cash_session_id', 'status'], 'sales_restaurant_session_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign('sales_restaurant_cash_session_fk');
            $table->dropForeign('sales_restaurant_cancelled_user_fk');
            $table->dropForeign('sales_restaurant_parent_sale_fk');
            $table->dropIndex('sales_restaurant_session_status_idx');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'cash_session_id',
                'status',
                'cancelled_at',
                'cancelled_by_user_id',
                'cancel_reason',
                'parent_sale_id',
                'document_type',
                'customer_fiscal_data',
            ]);
        });
    }
};
