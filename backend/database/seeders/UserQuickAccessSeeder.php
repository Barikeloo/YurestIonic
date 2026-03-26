<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserQuickAccessSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $deviceId = 'seed-device-001';

        $users = DB::table('users')
            ->join('restaurants', 'restaurants.id', '=', 'users.restaurant_id')
            ->whereIn('users.email', [
                'admin@tpv.local',
                'supervisor@tpv.local',
                'ana@tpv.local',
            ])
            ->select('users.id as user_id', 'users.restaurant_id')
            ->get();

        foreach ($users as $index => $user) {
            DB::table('user_quick_accesses')->updateOrInsert(
                [
                    'restaurant_id' => $user->restaurant_id,
                    'user_id' => $user->user_id,
                    'device_id' => $deviceId,
                ],
                [
                    'last_login_at' => $now->copy()->subMinutes($index),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
}
