<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password');
        $adminPin = Hash::make('1234');
        $supervisorPin = Hash::make('1235');
        $operatorPinA = Hash::make('1236');
        $operatorPinB = Hash::make('1237');
        $restaurantId = DB::table('restaurants')->first()?->id;

        if (! $restaurantId) {
            return; // No restaurant to seed users
        }

        DB::table('users')->upsert([
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'role' => 'admin',
                'image_src' => null,
                'name' => 'Admin TPV',
                'email' => 'admin@tpv.local',
                'password' => $password,
                'pin' => $adminPin,
                'email_verified_at' => $now,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'role' => 'supervisor',
                'image_src' => null,
                'name' => 'Supervisor TPV',
                'email' => 'supervisor@tpv.local',
                'password' => $password,
                'pin' => $supervisorPin,
                'email_verified_at' => $now,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'role' => 'admin',
                'image_src' => null,
                'name' => 'Ana Camarera',
                'email' => 'ana@tpv.local',
                'password' => $password,
                'pin' => $operatorPinA,
                'email_verified_at' => $now,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'role' => 'operator',
                'image_src' => null,
                'name' => 'Luis Camarero',
                'email' => 'luis@tpv.local',
                'password' => $password,
                'pin' => $operatorPinB,
                'email_verified_at' => $now,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ], ['email'], ['restaurant_id', 'uuid', 'role', 'image_src', 'name', 'password', 'pin', 'email_verified_at', 'updated_at', 'deleted_at']);
    }
}
