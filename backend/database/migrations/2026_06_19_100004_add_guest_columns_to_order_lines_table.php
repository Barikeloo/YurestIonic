<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('order_lines', 'origin')) {
                $table->enum('origin', ['tpv', 'guest'])->default('tpv')->after('tax_percentage');
            }

            if (! Schema::hasColumn('order_lines', 'send_status')) {
                $table->enum('send_status', ['pending', 'sent'])->default('sent')->after('origin');
            }

            if (! Schema::hasColumn('order_lines', 'guest_session_id')) {
                // char(36) to store guest_sessions.uuid — no FK since type differs from internal id
                $table->char('guest_session_id', 36)->nullable()->after('send_status');
            }

            if (! Schema::hasColumn('order_lines', 'guest_name')) {
                $table->string('guest_name', 100)->nullable()->after('guest_session_id');
            }

            if (! Schema::hasColumn('order_lines', 'guest_round_id')) {
                $table->char('guest_round_id', 36)->nullable()->after('guest_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $columns = array_filter(
                ['origin', 'send_status', 'guest_session_id', 'guest_name', 'guest_round_id'],
                static fn (string $col): bool => Schema::hasColumn('order_lines', $col),
            );

            if ($columns !== []) {
                $table->dropColumn(array_values($columns));
            }
        });
    }
};
