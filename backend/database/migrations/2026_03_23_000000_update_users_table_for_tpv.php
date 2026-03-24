<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('uuid')->update(['uuid' => (string) Str::uuid()]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('operator')->after('uuid');
            $table->string('image_src')->nullable()->after('role');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['role', 'image_src']);
        });
    }
};
