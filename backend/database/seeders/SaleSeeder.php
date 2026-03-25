<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $orders = DB::table('orders')->get(['id', 'restaurant_id']);

        if ($orders->isEmpty()) {
            return;
        }

        $now = now();

        // Create 2 sales from orders
        foreach ($orders->take(2) as $order) {
            $restaurantId = (int) $order->restaurant_id;
            $users = DB::table('users')
                ->where('restaurant_id', $restaurantId)
                ->pluck('id', 'email');

            if ($users->isEmpty()) {
                continue;
            }

            $saleId = DB::table('sales')->insertGetId([
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'order_id' => $order->id,
                'user_id' => $users['ana@tpv.local'] ?? null,
                'ticket_number' => null,
                'value_date' => now(),
                'total' => 1000,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);

            // Add lines to sale if there are order_lines
            $orderLinesList = DB::table('order_lines')
                ->where('restaurant_id', $restaurantId)
                ->where('order_id', $order->id)
                ->pluck('id')
                ->toArray();

            if (empty($orderLinesList)) {
                continue;
            }

            foreach ($orderLinesList as $orderLineId) {
                $orderLine = DB::table('order_lines')->find($orderLineId);

                DB::table('sales_lines')->insert([
                    'restaurant_id' => $restaurantId,
                    'uuid' => (string) Str::uuid(),
                    'sale_id' => $saleId,
                    'order_line_id' => $orderLineId,
                    'user_id' => $users['ana@tpv.local'] ?? null,
                    'quantity' => $orderLine->quantity,
                    'price' => $orderLine->price,
                    'tax_percentage' => $orderLine->tax_percentage,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]);
            }
        }
    }
}
