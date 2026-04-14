<?php

namespace Tests\Feature\Order;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class OrderCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_full_crud_flow(): void
    {
        $restaurantUuid = (string) Str::uuid();
        $zoneUuid = (string) Str::uuid();
        $tableUuid = (string) Str::uuid();
        $userUuid = (string) Str::uuid();

        $restaurantId = DB::table('restaurants')->insertGetId([
            'uuid' => $restaurantUuid,
            'name' => 'R Test',
            'legal_name' => 'R Test S.L.',
            'tax_id' => 'B99999999',
            'email' => 'rtest@local.dev',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $zoneId = DB::table('zones')->insertGetId([
            'uuid' => $zoneUuid,
            'restaurant_id' => $restaurantId,
            'name' => 'Zona Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tables')->insert([
            'uuid' => $tableUuid,
            'restaurant_id' => $restaurantId,
            'zone_id' => $zoneId,
            'name' => 'Mesa 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'uuid' => $userUuid,
            'restaurant_id' => $restaurantId,
            'role' => 'operator',
            'image_src' => null,
            'name' => 'User Test',
            'email' => 'user.order@test.dev',
            'pin' => '1234',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = ['auth_user_id' => $userUuid];

        $createResponse = $this->withSession($session)->postJson('/api/tpv/orders', [
            'restaurant_id' => $restaurantUuid,
            'table_id' => $tableUuid,
            'opened_by_user_id' => $userUuid,
            'diners' => 4,
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJsonFragment([
            'restaurant_id' => $restaurantUuid,
            'table_id' => $tableUuid,
            'opened_by_user_id' => $userUuid,
            'diners' => 4,
        ]);

        $orderId = $createResponse->json('id');

        $this->withSession($session)->getJson('/api/tpv/orders')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $orderId,
            ]);

        $this->withSession($session)->getJson("/api/tpv/orders/{$orderId}")
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $orderId,
                'restaurant_id' => $restaurantUuid,
            ]);

        $this->withSession($session)->putJson("/api/tpv/orders/{$orderId}", [
            'diners' => 6,
        ])
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $orderId,
                'diners' => 6,
            ]);

        $this->withSession($session)->deleteJson("/api/tpv/orders/{$orderId}")
            ->assertStatus(204);

        $this->withSession($session)->getJson("/api/tpv/orders/{$orderId}")
            ->assertStatus(404);
    }
}
