<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Raw MODIFY is MySQL-specific; SQLite (used in tests) is loosely typed
        // and keeps the original column, so this is a no-op there.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE scheduled_reports MODIFY COLUMN time TIME NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE scheduled_reports MODIFY COLUMN time VARCHAR(5) NOT NULL');
        }
    }
};
