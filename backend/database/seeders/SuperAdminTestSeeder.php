<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class SuperAdminTestSeeder extends Seeder
{
    public function run(): void
    {
        // Create test superadmin with known credentials for testing
        DB::table('super_admins')->insert([
            'uuid' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1',
            'name' => 'Developer (Test)',
            'email' => 'dev@tpv.local',
            'password' => Hash::make('dev123456'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create some test restaurants with different companies (tax_ids)
        $restaurants = [
            [
                'uuid' => '11111111-1111-4111-8111-111111111111',
                'name' => 'Restaurant Zentral',
                'legal_name' => 'Restaurant Zentral SL',
                'tax_id' => 'B12345678',
                'email' => 'zentral@tpv.local',
                'password' => Hash::make('rest123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => '22222222-2222-4222-8222-222222222222',
                'name' => 'Restaurant Zentral Sucursal',
                'legal_name' => 'Restaurant Zentral SL',
                'tax_id' => 'B12345678',
                'email' => 'zentral-sucursal@tpv.local',
                'password' => Hash::make('rest123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => '33333333-3333-4333-8333-333333333333',
                'name' => 'Pizzería Italia',
                'legal_name' => 'Pizzería Italia SL',
                'tax_id' => 'A87654321',
                'email' => 'italia@tpv.local',
                'password' => Hash::make('rest123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('restaurants')->insert($restaurants);

        // Create test admin user for the first restaurant
        DB::table('users')->insert([
            'restaurant_id' => 1, // ID of first restaurant
            'uuid' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb1',
            'name' => 'Admin Zentral',
            'email' => 'admin@zentral.tpv.local',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
