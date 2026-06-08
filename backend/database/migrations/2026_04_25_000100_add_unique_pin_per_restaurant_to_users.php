<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

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
