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

        DB::table('users')->upsert([
            [
                'uuid' => (string) Str::uuid(),
                'role' => 'admin',
                'image_src' => null,
                'name' => 'Admin TPV',
                'email' => 'admin@tpv.local',
                'password' => $password,
                'email_verified_at' => $now,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'role' => 'supervisor',
                'image_src' => null,
                'name' => 'Supervisor TPV',
                'email' => 'supervisor@tpv.local',
                'password' => $password,
                'email_verified_at' => $now,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'role' => 'operator',
                'image_src' => null,
                'name' => 'Ana Camarera',
                'email' => 'ana@tpv.local',
                'password' => $password,
                'email_verified_at' => $now,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'role' => 'operator',
                'image_src' => null,
                'name' => 'Luis Camarero',
                'email' => 'luis@tpv.local',
                'password' => $password,
                'email_verified_at' => $now,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ], ['email'], ['name', 'password', 'updated_at', 'deleted_at']);
    }
}
