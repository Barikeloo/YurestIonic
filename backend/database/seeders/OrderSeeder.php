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

        if (empty($restaurants)) {
            return; // No data to seed
        }

        $orders = [];
        for ($i = 0; $i < 3; $i++) {
            $restaurantId = $restaurants[array_rand($restaurants)];
            $tables = DB::table('tables')
                ->where('restaurant_id', $restaurantId)
                ->pluck('id')
                ->toArray();
            $users = DB::table('users')
                ->where('restaurant_id', $restaurantId)
                ->pluck('id')
                ->toArray();

            if (empty($tables) || empty($users)) {
                continue;
            }

            $orders[] = [
                'uuid' => (string) Str::uuid(),
                'restaurant_id' => $restaurantId,
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

        if (! empty($orders)) {
            DB::table('orders')->insert($orders);
        }
    }
}
