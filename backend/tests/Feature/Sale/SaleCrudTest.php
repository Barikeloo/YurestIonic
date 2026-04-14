<?php

namespace Tests\Feature\Sale;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SaleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_cannot_be_closed_without_lines(): void
    {
        $restaurantUuid = (string) Str::uuid();
        $zoneUuid = (string) Str::uuid();
        $tableUuid = (string) Str::uuid();
        $userUuid = (string) Str::uuid();
        $orderUuid = (string) Str::uuid();

        $restaurantId = DB::table('restaurants')->insertGetId([
            'uuid' => $restaurantUuid,
            'name' => 'R Sale Close Test',
            'legal_name' => 'R Sale Close Test S.L.',
            'tax_id' => 'B77777777',
            'email' => 'rsale-close@local.dev',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $zoneId = DB::table('zones')->insertGetId([
            'uuid' => $zoneUuid,
            'restaurant_id' => $restaurantId,
            'name' => 'Zona Close',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tableId = DB::table('tables')->insertGetId([
            'uuid' => $tableUuid,
            'restaurant_id' => $restaurantId,
            'zone_id' => $zoneId,
            'name' => 'Mesa Close',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'uuid' => $userUuid,
            'restaurant_id' => $restaurantId,
            'role' => 'operator',
            'image_src' => null,
            'name' => 'User Close',
            'email' => 'user.close@test.dev',
            'pin' => '1234',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders')->insert([
            'uuid' => $orderUuid,
            'restaurant_id' => $restaurantId,
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

        $session = ['auth_user_id' => $userUuid];

        $createResponse = $this->withSession($session)->postJson('/api/tpv/sales', [
            'restaurant_id' => $restaurantUuid,
            'order_id' => $orderUuid,
            'opened_by_user_id' => $userUuid,
        ]);

        $createResponse->assertStatus(201);
        $saleId = $createResponse->json('id');

        $this->withSession($session)->putJson("/api/tpv/sales/{$saleId}", [
            'closed_by_user_id' => $userUuid,
            'ticket_number' => 1001,
        ])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'A sale must have at least one line before closing.',
            ]);
    }

    public function test_sale_full_crud_flow(): void
    {
        $restaurantUuid = (string) Str::uuid();
        $zoneUuid = (string) Str::uuid();
        $tableUuid = (string) Str::uuid();
        $userUuid = (string) Str::uuid();
        $orderUuid = (string) Str::uuid();

        $restaurantId = DB::table('restaurants')->insertGetId([
            'uuid' => $restaurantUuid,
            'name' => 'R Sale Test',
            'legal_name' => 'R Sale Test S.L.',
            'tax_id' => 'B88888888',
            'email' => 'rsale@local.dev',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $zoneId = DB::table('zones')->insertGetId([
            'uuid' => $zoneUuid,
            'restaurant_id' => $restaurantId,
            'name' => 'Zona Sale',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tableId = DB::table('tables')->insertGetId([
            'uuid' => $tableUuid,
            'restaurant_id' => $restaurantId,
            'zone_id' => $zoneId,
            'name' => 'Mesa Sale',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'uuid' => $userUuid,
            'restaurant_id' => $restaurantId,
            'role' => 'operator',
            'image_src' => null,
            'name' => 'User Sale',
            'email' => 'user.sale@test.dev',
            'pin' => '1234',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders')->insert([
            'uuid' => $orderUuid,
            'restaurant_id' => $restaurantId,
            'status' => 'open',
            'table_id' => $tableId,
            'opened_by_user_id' => $userId,
            'closed_by_user_id' => null,
            'diners' => 3,
            'opened_at' => now(),
            'closed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = ['auth_user_id' => $userUuid];

        $createResponse = $this->withSession($session)->postJson('/api/tpv/sales', [
            'restaurant_id' => $restaurantUuid,
            'order_id' => $orderUuid,
            'opened_by_user_id' => $userUuid,
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJsonFragment([
            'restaurant_id' => $restaurantUuid,
            'order_id' => $orderUuid,
            'opened_by_user_id' => $userUuid,
            'total' => 0,
        ]);

        $saleId = $createResponse->json('id');

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
            'name' => 'Cafe',
            'price' => 1000,
            'stock' => 50,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = (int) DB::table('orders')->where('uuid', $orderUuid)->value('id');
        $saleInternalId = (int) DB::table('sales')->where('uuid', $saleId)->value('id');

        $orderLineId = DB::table('order_lines')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'order_id' => $orderId,
            'product_id' => $productId,
            'user_id' => $userId,
            'quantity' => 2,
            'price' => 1000,
            'tax_percentage' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_lines')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'sale_id' => $saleInternalId,
            'order_line_id' => $orderLineId,
            'product_id' => $productId,
            'user_id' => $userId,
            'quantity' => 2,
            'price' => 1000,
            'tax_percentage' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession($session)->getJson('/api/tpv/sales')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $saleId,
            ]);

        $this->withSession($session)->getJson("/api/tpv/sales/{$saleId}")
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $saleId,
                'restaurant_id' => $restaurantUuid,
            ]);

        $this->withSession($session)->putJson("/api/tpv/sales/{$saleId}", [
            'closed_by_user_id' => $userUuid,
            'ticket_number' => 1001,
        ])
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $saleId,
                'ticket_number' => 1001,
                'closed_by_user_id' => $userUuid,
                'total' => 2200,
            ]);

        $this->withSession($session)->deleteJson("/api/tpv/sales/{$saleId}")
            ->assertStatus(204);

        $this->withSession($session)->getJson("/api/tpv/sales/{$saleId}")
            ->assertStatus(404);
    }
}
