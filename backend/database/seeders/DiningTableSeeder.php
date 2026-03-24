<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DiningTableSeeder extends Seeder
{
    public function run(): void
    {
        $restaurantId = DB::table('restaurants')->first()?->id;
        $zoneIds = DB::table('zones')->pluck('id', 'name');

        if (!$restaurantId || !$zoneIds->has('Salon') || !$zoneIds->has('Terraza')) {
            return;
        }

        $now = now();

        DB::table('tables')->upsert([
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'zone_id' => $zoneIds['Salon'],
                'name' => 'S1',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'zone_id' => $zoneIds['Salon'],
                'name' => 'S2',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'zone_id' => $zoneIds['Terraza'],
                'name' => 'T1',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'zone_id' => $zoneIds['Terraza'],
                'name' => 'T2',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ], ['name'], ['zone_id', 'updated_at', 'deleted_at']);
    }
}
