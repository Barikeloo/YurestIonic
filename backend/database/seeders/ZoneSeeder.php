<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $restaurantId = DB::table('restaurants')->first()?->id;

        if (!$restaurantId) {
            return;
        }

        DB::table('zones')->upsert([
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'name' => 'Salon',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'name' => 'Terraza',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ], ['name'], ['updated_at', 'deleted_at']);
    }
}
