<?php

namespace Tests\Feature\Restaurant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DeleteRestaurantCascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_restaurant_cascade_deletes_all_related_data(): void
    {
        $session = $this->createSuperAdminSession();
        $restaurantUuid = (string) Str::uuid();

        // Create restaurant
        DB::table('restaurants')->insert([
            'uuid' => $restaurantUuid,
            'name' => 'Restaurant to Delete',
            'legal_name' => 'Restaurant to Delete S.L.',
            'tax_id' => 'D12345678',
            'email' => 'delete@test.local',
            'password' => Hash::make('pass'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create users for this restaurant
        $userName = 'test-user-' . Str::random(5);
        $userUuid = (string) Str::uuid();
        DB::table('users')->insert([
            'uuid' => $userUuid,
            'restaurant_id' => DB::table('restaurants')->where('uuid', $restaurantUuid)->first()->id,
            'name' => $userName,
            'email' => $userName . '@test.local',
            'role' => 'operator',
            'password' => Hash::make('pass'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create family
        $familyId = DB::table('families')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => DB::table('restaurants')->where('uuid', $restaurantUuid)->first()->id,
            'name' => 'Test Family',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create tax
        $taxId = DB::table('taxes')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => DB::table('restaurants')->where('uuid', $restaurantUuid)->first()->id,
            'name' => 'Test Tax',
            'percentage' => 21,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create product
        $productId = DB::table('products')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => DB::table('restaurants')->where('uuid', $restaurantUuid)->first()->id,
            'family_id' => $familyId,
            'tax_id' => $taxId,
            'name' => 'Test Product',
            'price' => 1000,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create zone
        $zoneId = DB::table('zones')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => DB::table('restaurants')->where('uuid', $restaurantUuid)->first()->id,
            'name' => 'Test Zone',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create table
        $tableId = DB::table('tables')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => DB::table('restaurants')->where('uuid', $restaurantUuid)->first()->id,
            'zone_id' => $zoneId,
            'name' => 'Table 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create order
        $restaurantId = DB::table('restaurants')->where('uuid', $restaurantUuid)->first()->id;
        $userId = DB::table('users')->where('uuid', $userUuid)->first()->id;

        $orderId = DB::table('orders')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'status' => 'open',
            'table_id' => $tableId,
            'opened_by_user_id' => $userId,
            'diners' => 2,
            'opened_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create order line
        DB::table('order_lines')->insert([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'order_id' => $orderId,
            'product_id' => $productId,
            'user_id' => $userId,
            'quantity' => 2,
            'price' => 500,
            'tax_percentage' => 21,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create sale
        $saleId = DB::table('sales')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'ticket_number' => 1,
            'value_date' => now(),
            'total' => 1210,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create sale line
        DB::table('sales_lines')->insert([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $restaurantId,
            'sale_id' => $saleId,
            'user_id' => $userId,
            'quantity' => 2,
            'price' => 500,
            'tax_percentage' => 21,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify data exists before deletion
        $this->assertTrue(DB::table('restaurants')->where('uuid', $restaurantUuid)->exists());
        $this->assertTrue(DB::table('users')->where('uuid', $userUuid)->exists());
        $this->assertTrue(DB::table('families')->where('id', $familyId)->exists());
        $this->assertTrue(DB::table('taxes')->where('id', $taxId)->exists());
        $this->assertTrue(DB::table('products')->where('id', $productId)->exists());
        $this->assertTrue(DB::table('zones')->where('id', $zoneId)->exists());
        $this->assertTrue(DB::table('tables')->where('id', $tableId)->exists());
        $this->assertTrue(DB::table('orders')->where('id', $orderId)->exists());
        $this->assertTrue(DB::table('order_lines')->where('order_id', $orderId)->exists());
        $this->assertTrue(DB::table('sales')->where('id', $saleId)->exists());
        $this->assertTrue(DB::table('sales_lines')->where('sale_id', $saleId)->exists());

        // Delete restaurant
        $response = $this->withSession($session['session'])->deleteJson("/api/superadmin/restaurants/{$restaurantUuid}");
        $response->assertStatus(204);

        // Verify restaurant is soft-deleted (deleted_at is set)
        $restaurant = DB::table('restaurants')->where('uuid', $restaurantUuid)->first();
        $this->assertNotNull($restaurant->deleted_at);

        // Verify all related data is also soft-deleted (cascade delete)
        $user = DB::table('users')->where('uuid', $userUuid)->first();
        $this->assertNotNull($user->deleted_at, 'User should be soft-deleted when restaurant is deleted');

        $family = DB::table('families')->where('id', $familyId)->first();
        $this->assertNotNull($family->deleted_at, 'Family should be soft-deleted when restaurant is deleted');

        $tax = DB::table('taxes')->where('id', $taxId)->first();
        $this->assertNotNull($tax->deleted_at, 'Tax should be soft-deleted when restaurant is deleted');

        $product = DB::table('products')->where('id', $productId)->first();
        $this->assertNotNull($product->deleted_at, 'Product should be soft-deleted when restaurant is deleted');

        $zone = DB::table('zones')->where('id', $zoneId)->first();
        $this->assertNotNull($zone->deleted_at, 'Zone should be soft-deleted when restaurant is deleted');

        $table = DB::table('tables')->where('id', $tableId)->first();
        $this->assertNotNull($table->deleted_at, 'Table should be soft-deleted when restaurant is deleted');

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertNotNull($order->deleted_at, 'Order should be soft-deleted when restaurant is deleted');

        $orderLine = DB::table('order_lines')->where('order_id', $orderId)->first();
        $this->assertNotNull($orderLine->deleted_at, 'Order line should be soft-deleted when restaurant is deleted');

        $sale = DB::table('sales')->where('id', $saleId)->first();
        $this->assertNotNull($sale->deleted_at, 'Sale should be soft-deleted when restaurant is deleted');

        $saleLine = DB::table('sales_lines')->where('sale_id', $saleId)->first();
        $this->assertNotNull($saleLine->deleted_at, 'Sale line should be soft-deleted when restaurant is deleted');
    }
}
