<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->integer('snapshot_total_cents')->nullable()->after('amount_cents');
            $table->integer('snapshot_paid_cents')->nullable()->after('snapshot_total_cents');
            $table->integer('snapshot_remaining_cents')->nullable()->after('snapshot_paid_cents');
        });
    }

    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropColumn([
                'snapshot_total_cents',
                'snapshot_paid_cents',
                'snapshot_remaining_cents',
            ]);
        });
    }
};
