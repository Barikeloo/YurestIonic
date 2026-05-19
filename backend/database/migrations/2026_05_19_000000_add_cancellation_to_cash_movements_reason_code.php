<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE cash_movements MODIFY reason_code ENUM(
                'change_refill',
                'supplier_payment',
                'tip_declared',
                'sangria',
                'adjustment',
                'cancellation',
                'other'
            ) NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE cash_movements MODIFY reason_code ENUM(
                'change_refill',
                'supplier_payment',
                'tip_declared',
                'sangria',
                'adjustment',
                'other'
            ) NOT NULL"
        );
    }
};
