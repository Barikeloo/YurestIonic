<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $restaurants = DB::table('restaurants')->pluck('id')->toArray();
        $tables = DB::table('tables')->pluck('id')->toArray();
        $users = DB::table('users')->pluck('id')->toArray();

        if (empty($restaurants) || empty($tables) || empty($users)) {
            return; // No data to seed
        }

        $orders = [];
        for ($i = 0; $i < 3; $i++) {
            $orders[] = [
                'uuid' => (string) Str::uuid(),
                'restaurant_id' => $restaurants[array_rand($restaurants)],
                'status' => 'open',
                'table_id' => $tables[array_rand($tables)],
                'opened_by_user_id' => $users[array_rand($users)],
                'closed_by_user_id' => null,
                'diners' => rand(1, 6),
                'opened_at' => now()->subHours(rand(1, 24)),
                'closed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];
        }

        DB::table('orders')->insert($orders);
    }
}
