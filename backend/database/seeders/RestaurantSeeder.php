<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('restaurant123');

        DB::table('restaurants')->upsert([
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Restaurant Principal',
                'legal_name' => 'Restaurant Principal S.L.',
                'tax_id' => 'B12345678',
                'email' => 'info@restaurant.local',
                'password' => $password,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Restaurant Secondary',
                'legal_name' => 'Restaurant Secondary S.A.',
                'tax_id' => 'B87654321',
                'email' => 'info2@restaurant.local',
                'password' => $password,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ], ['email']);
    }
}
