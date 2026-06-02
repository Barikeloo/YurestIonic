<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
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
        } catch (\Illuminate\Database\QueryException) {
        }
    }

    public function down(): void
    {
        try {
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
        } catch (\Illuminate\Database\QueryException) {
        }
    }
};
