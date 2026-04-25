<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullify duplicate PINs keeping only the first user per (restaurant_id, pin) pair.
        // Duplicates can exist in dev/demo data; production should have none.
        // Uses row-by-row PHP iteration to stay compatible with SQLite (used in tests).
        $duplicateGroups = DB::table('users')
            ->select('restaurant_id', 'pin', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('pin')
            ->groupBy('restaurant_id', 'pin')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            DB::table('users')
                ->where('restaurant_id', $group->restaurant_id)
                ->where('pin', $group->pin)
                ->where('id', '!=', $group->keep_id)
                ->update(['pin' => null]);
        }

        Schema::table('users', function (Blueprint $table) {
            // MySQL treats each NULL as distinct, so multiple users without PIN are allowed.
            $table->unique(['restaurant_id', 'pin'], 'users_restaurant_pin_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_restaurant_pin_unique');
        });
    }
};
