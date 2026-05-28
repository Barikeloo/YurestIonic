<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('category', 32)->after('action');
            $table->string('severity', 16)->after('category');
            $table->string('summary', 500)->after('severity');
            $table->text('reason')->nullable()->after('summary');
            $table->uuid('session_id')->nullable()->after('reason');
            $table->string('anomaly_kind', 64)->nullable()->after('session_id');
            $table->char('integrity_hash', 64)->after('anomaly_kind');
            $table->char('prev_hash', 64)->nullable()->after('integrity_hash');
            $table->json('metadata')->nullable()->after('prev_hash');

            $table->index(['restaurant_id', 'category', 'created_at'], 'audit_logs_restaurant_category_created_idx');
            $table->index(['restaurant_id', 'severity', 'created_at'], 'audit_logs_restaurant_severity_created_idx');
            $table->index(['restaurant_id', 'anomaly_kind'], 'audit_logs_restaurant_anomaly_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_restaurant_category_created_idx');
            $table->dropIndex('audit_logs_restaurant_severity_created_idx');
            $table->dropIndex('audit_logs_restaurant_anomaly_idx');

            $table->dropColumn([
                'category',
                'severity',
                'summary',
                'reason',
                'session_id',
                'anomaly_kind',
                'integrity_hash',
                'prev_hash',
                'metadata',
            ]);
        });
    }
};
