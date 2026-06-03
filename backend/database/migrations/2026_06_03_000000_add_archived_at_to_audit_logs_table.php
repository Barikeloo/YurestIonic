<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `archived_at` to audit_logs for the weekly archival job.
 *
 * Retention policy (see PLAN_AUDIT_RETENTION.md and README §6 "Retención"):
 *  - Día 0 – 90:  registro activo (visible por defecto en la UI/API).
 *  - Día 90 – 6 años: archivado (archived_at IS NOT NULL). Sólo accesible para
 *    admin con include_archived=1. Justificado por el Código de Comercio (Art. 30,
 *    6 años) y la LGT (Art. 66, 4 años) además del régimen de TicketBAI / VeriFactu.
 *  - Nunca se borra. La política del cliente es retención indefinida.
 *
 * El índice compuesto (restaurant_id, archived_at, created_at) acelera los dos
 * patrones de query habituales: listado paginado de activos por restaurante y
 * scan periódico del job que busca filas a archivar.
 */
return new class extends Migration
{
    public function up(): void
    {
        // `archived_at` was added by hand outside of migrations in earlier
        // exploratory work, so the column may already exist when this runs
        // against pre-existing databases. Idempotent against both MySQL
        // (host DB) and SQLite (test DB).
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
