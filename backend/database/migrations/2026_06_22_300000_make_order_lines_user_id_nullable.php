<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if (! $isSqlite) {
            Schema::table('order_lines', function (Blueprint $table) {
                $table->dropForeign('order_lines_user_fk');
            });
        }

        Schema::table('order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        if (! $isSqlite) {
            Schema::table('order_lines', function (Blueprint $table) {
                $table->foreign('user_id', 'order_lines_user_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if (! $isSqlite) {
            Schema::table('order_lines', function (Blueprint $table) {
                $table->dropForeign('order_lines_user_fk');
            });
        }

        Schema::table('order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        if (! $isSqlite) {
            Schema::table('order_lines', function (Blueprint $table) {
                $table->foreign('user_id', 'order_lines_user_fk')->references('id')->on('users');
            });
        }
    }
};
