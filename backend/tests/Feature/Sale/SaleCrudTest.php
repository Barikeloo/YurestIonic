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

        $createResponse = $this->withSession($session)->postJson('/api/sales', [
            'restaurant_id' => $restaurantUuid,
            'order_id' => $orderUuid,
            'user_id' => $userUuid,
            'total' => 1500,
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJsonFragment([
            'restaurant_id' => $restaurantUuid,
            'order_id' => $orderUuid,
            'user_id' => $userUuid,
            'total' => 1500,
        ]);

        $saleId = $createResponse->json('id');

        $this->withSession($session)->getJson('/api/sales')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $saleId,
            ]);

        $this->withSession($session)->getJson("/api/sales/{$saleId}")
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $saleId,
                'restaurant_id' => $restaurantUuid,
            ]);

        $this->withSession($session)->putJson("/api/sales/{$saleId}", [
            'ticket_number' => 1001,
            'total' => 1900,
        ])
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $saleId,
                'ticket_number' => 1001,
                'total' => 1900,
            ]);

        $this->withSession($session)->deleteJson("/api/sales/{$saleId}")
            ->assertStatus(204);

        $this->withSession($session)->getJson("/api/sales/{$saleId}")
            ->assertStatus(404);
    }
}
