<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // TIMESTAMP columns cap at 2038 on MySQL, but inactive reports park
        // next_run_at at 9999-12-31. Switch to DATETIME. MySQL-only; SQLite
        // (tests) is loosely typed and keeps the original column.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE scheduled_reports MODIFY COLUMN last_run_at DATETIME NULL');
            DB::statement('ALTER TABLE scheduled_reports MODIFY COLUMN next_run_at DATETIME NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE scheduled_reports MODIFY COLUMN last_run_at TIMESTAMP NULL');
            DB::statement('ALTER TABLE scheduled_reports MODIFY COLUMN next_run_at TIMESTAMP NOT NULL');
        }
    }
};
