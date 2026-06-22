<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_opened_by_user_fk');
            $table->unsignedBigInteger('opened_by_user_id')->nullable()->change();
            $table->foreign('opened_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_opened_by_user_id_foreign');
            $table->unsignedBigInteger('opened_by_user_id')->nullable(false)->change();
            $table->foreign('opened_by_user_id')->references('id')->on('users');
        });
    }
};
