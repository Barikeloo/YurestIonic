<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('created_at');
            }
        });

        if (! Schema::hasIndex('audit_logs', 'audit_logs_restaurant_archived_created_idx')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(
                    ['restaurant_id', 'archived_at', 'created_at'],
                    'audit_logs_restaurant_archived_created_idx',
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('audit_logs', 'audit_logs_restaurant_archived_created_idx')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('audit_logs_restaurant_archived_created_idx');
            });
        }

        if (Schema::hasColumn('audit_logs', 'archived_at')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn('archived_at');
            });
        }
    }
};
