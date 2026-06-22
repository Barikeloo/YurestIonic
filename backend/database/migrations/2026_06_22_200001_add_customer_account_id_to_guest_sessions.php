<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->foreignId('customer_account_id')
                ->nullable()
                ->after('identity_mode')
                ->constrained('customer_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->dropForeign(['customer_account_id']);
            $table->dropColumn('customer_account_id');
        });
    }
};
