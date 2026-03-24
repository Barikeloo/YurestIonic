<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderLineSeeder extends Seeder
{
    public function run(): void
    {
        $restaurantId = DB::table('restaurants')->first()?->id;
        $orders = DB::table('orders')->pluck('id')->toArray();
        $products = DB::table('products')->pluck('id', 'name');
        $users = DB::table('users')->pluck('id', 'email');

        if (!$restaurantId || empty($orders) || empty($products) || empty($users)) {
            return;
        }

        $now = now();

        // Agregar líneas a cada orden
        foreach ($orders as $orderId) {
            for ($i = 0; $i < rand(1, 3); $i++) {
                $productName = array_rand(['Cafe' => 1, 'Cerveza' => 1, 'Bocadillo' => 1]);
                $productId = $products[$productName] ?? null;

                if (!$productId) {
                    continue;
                }

                $product = DB::table('products')->find($productId);
                $tax = DB::table('taxes')->find($product->tax_id);

                DB::table('order_lines')->insert([
                    'restaurant_id' => $restaurantId,
                    'uuid' => (string) Str::uuid(),
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'user_id' => $users['ana@tpv.local'] ?? null,
                    'quantity' => rand(1, 3),
                    'price' => $product->price,
                    'tax_percentage' => $tax->percentage ?? 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]);
            }
        }
    }
}
