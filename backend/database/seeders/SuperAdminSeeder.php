<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('SUPERADMIN_NAME', 'Platform Superadmin');
        $email = env('SUPERADMIN_EMAIL', 'superadmin@tpv.local');
        $password = env('SUPERADMIN_PASSWORD', 'superadmin123');

        DB::table('super_admins')->updateOrInsert(
            ['email' => $email],
            [
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'password' => Hash::make($password),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
