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
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign('orders_opened_by_user_fk');
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('opened_by_user_id')->nullable()->change();
        });

        if (! $isSqlite) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('opened_by_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if (! $isSqlite) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['opened_by_user_id']);
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('opened_by_user_id')->nullable(false)->change();
        });

        if (! $isSqlite) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('opened_by_user_id')->references('id')->on('users');
            });
        }
    }
};
