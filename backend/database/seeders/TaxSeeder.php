<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $restaurantId = DB::table('restaurants')->first()?->id;

        if (!$restaurantId) {
            return;
        }

        DB::table('taxes')->upsert([
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'name' => 'IVA Superreducido',
                'percentage' => 4,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'name' => 'IVA Reducido',
                'percentage' => 10,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'name' => 'IVA General',
                'percentage' => 21,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ], ['name'], ['percentage', 'updated_at', 'deleted_at']);
    }
}
