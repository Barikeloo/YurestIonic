<?php

namespace Tests\Feature\Sale;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AddLineToSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_sales_lines_returns_422_when_invalid_uuids(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/sales/lines', [
            'restaurant_id' => $tenant['restaurant_uuid'],
            'sale_id' => 'not-a-uuid',
            'order_line_id' => 'not-a-uuid',
            'user_id' => 'not-a-uuid',
            'quantity' => 2,
            'price' => 1000,
            'tax_percentage' => 21,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sale_id', 'order_line_id', 'user_id']);
    }

    public function test_post_sales_lines_returns_422_when_quantity_is_invalid(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/sales/lines', [
            'restaurant_id' => $tenant['restaurant_uuid'],
            'sale_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'order_line_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'user_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'quantity' => -1,
            'price' => 1000,
            'tax_percentage' => 21,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity']);
    }

    public function test_post_sales_lines_returns_422_when_required_fields_missing(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/sales/lines', [
            'restaurant_id' => $tenant['restaurant_uuid'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sale_id', 'order_line_id', 'user_id', 'quantity', 'price', 'tax_percentage']);
    }

    public function test_post_sales_lines_returns_422_when_product_is_inactive(): void
    {
        $tenant = $this->createTenantSession();
        $restaurantId = $tenant['restaurant_id'];
        $restaurantUuid = $tenant['restaurant_uuid'];
        $userUuid = $tenant['user_uuid'];
        $userId = (int) DB::table('users')->where('uuid', $userUuid)->value('id');

        $zoneId = DB::table('zones')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'name' => 'Salon',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tableId = DB::table('tables')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'zone_id' => $zoneId,
            'name' => 'S1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderUuid = (string) Str::uuid();
        $orderId = DB::table('orders')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => $orderUuid,
            'status' => 'open',
            'table_id' => $tableId,
            'opened_by_user_id' => $userId,
            'closed_by_user_id' => null,
            'diners' => 2,
            'opened_at' => now(),
            'closed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $saleUuid = (string) Str::uuid();
        $saleId = DB::table('sales')->insertGetId([
            'restaurant_id' => $restaurantId,
            'table_id' => $tableId,
            'opened_by_user_id' => $userId,
            'closed_by_user_id' => null,
            'uuid' => $saleUuid,
            'order_id' => $orderId,
            'user_id' => $userId,
            'ticket_number' => null,
            'value_date' => now(),
            'total' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $taxId = DB::table('taxes')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA 10',
            'percentage' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $familyId = DB::table('families')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'name' => 'Bebidas',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'family_id' => $familyId,
            'tax_id' => $taxId,
            'name' => 'Cafe Inactivo',
            'price' => 150,
            'stock' => 10,
            'active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderLineUuid = (string) Str::uuid();
        DB::table('order_lines')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => $orderLineUuid,
            'order_id' => $orderId,
            'product_id' => $productId,
            'user_id' => $userId,
            'quantity' => 1,
            'price' => 150,
            'tax_percentage' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/sales/lines', [
            'restaurant_id' => $restaurantUuid,
            'sale_id' => $saleUuid,
            'order_line_id' => $orderLineUuid,
            'user_id' => $userUuid,
            'quantity' => 1,
            'price' => 150,
            'tax_percentage' => 10,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Only active products can be sold.',
        ]);
    }
}
