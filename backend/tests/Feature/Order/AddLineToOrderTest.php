<?php

namespace Tests\Feature\Order;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AddLineToOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_orders_lines_returns_422_when_invalid_uuids(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/orders/lines', [
            'order_id' => 'not-a-uuid',
            'product_id' => 'not-a-uuid',
            'quantity' => 2,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_id', 'product_id']);
    }

    public function test_post_orders_lines_returns_422_when_quantity_is_invalid(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/orders/lines', [
            'order_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'product_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'quantity' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity']);
    }

    public function test_post_orders_lines_returns_422_when_required_fields_missing(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/orders/lines', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_id', 'product_id', 'quantity']);
    }

    public function test_post_orders_lines_returns_422_when_product_is_inactive(): void
    {
        $tenant = $this->createTenantSession();
        $restaurantId = $tenant['restaurant_id'];
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

        $productUuid = (string) Str::uuid();
        DB::table('products')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => $productUuid,
            'family_id' => $familyId,
            'tax_id' => $taxId,
            'name' => 'Cafe Inactivo',
            'price' => 150,
            'stock' => 10,
            'active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderUuid = (string) Str::uuid();
        DB::table('orders')->insert([
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

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/orders/lines', [
            'order_id' => $orderUuid,
            'product_id' => $productUuid,
            'quantity' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Only active products can be sold.',
        ]);
    }
}
