<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_alerts', function (Blueprint $table): void {
            $table->uuid('audit_log_uuid')->nullable()->after('uuid')->index();
        });
    }

    public function down(): void
    {
        Schema::table('audit_alerts', function (Blueprint $table): void {
            $table->dropColumn('audit_log_uuid');
        });
    }
};
