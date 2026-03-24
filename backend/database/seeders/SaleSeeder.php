<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $restaurantId = DB::table('restaurants')->first()?->id;
        $orders = DB::table('orders')->pluck('id')->toArray();
        $users = DB::table('users')->pluck('id', 'email');

        if (!$restaurantId || empty($orders)) {
            return;
        }

        $now = now();

        // Create 2 sales from orders
        foreach (array_slice($orders, 0, min(2, count($orders))) as $orderId) {
            $saleId = DB::table('sales')->insertGetId([
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'order_id' => $orderId,
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
                ->where('order_id', $orderId)
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
