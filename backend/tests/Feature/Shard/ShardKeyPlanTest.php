<?php

namespace Tests\Feature\Shard;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ShardKeyPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_blocks_cross_restaurant_product_family_relation(): void
    {
        $restaurantAId = $this->createRestaurant('Restaurant A', 'a@local.test');
        $restaurantBId = $this->createRestaurant('Restaurant B', 'b@local.test');

        $familyId = DB::table('families')->insertGetId([
            'restaurant_id' => $restaurantAId,
            'uuid' => (string) Str::uuid(),
            'name' => 'Family A',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $taxId = DB::table('taxes')->insertGetId([
            'restaurant_id' => $restaurantBId,
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA 21',
            'percentage' => 21,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('products')->insert([
            'restaurant_id' => $restaurantBId,
            'uuid' => (string) Str::uuid(),
            'family_id' => $familyId,
            'tax_id' => $taxId,
            'name' => 'Cross Product',
            'price' => 1000,
            'stock' => 10,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_can_list_restaurants_with_minimal_fields(): void
    {
        $this->createRestaurant('Admin Test A', 'admin-a@local.test');
        $this->createRestaurant('Admin Test B', 'admin-b@local.test');

        $adminUuid = (string) Str::uuid();

        DB::table('users')->insert([
            'restaurant_id' => null,
            'uuid' => $adminUuid,
            'role' => 'admin',
            'name' => 'Platform Admin',
            'email' => 'platform-admin@local.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession([
            'auth_user_id' => $adminUuid,
        ])->getJson('/api/admin/restaurants');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonStructure([
            'data' => [
                [
                    'uuid',
                    'name',
                    'legal_name',
                    'tax_id',
                ],
            ],
        ]);
    }

    public function test_non_admin_cannot_list_restaurants_from_admin_endpoint(): void
    {
        $operatorUuid = (string) Str::uuid();

        DB::table('users')->insert([
            'restaurant_id' => null,
            'uuid' => $operatorUuid,
            'role' => 'operator',
            'name' => 'Operator User',
            'email' => 'operator@local.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'auth_user_id' => $operatorUuid,
        ])->getJson('/api/admin/restaurants')
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.',
            ]);
    }

    public function test_admin_must_select_restaurant_context_before_accessing_tenant_modules(): void
    {
        $adminUuid = (string) Str::uuid();

        DB::table('users')->insert([
            'restaurant_id' => null,
            'uuid' => $adminUuid,
            'role' => 'admin',
            'name' => 'Platform Admin',
            'email' => 'admin-context-required@local.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'auth_user_id' => $adminUuid,
        ])->getJson('/api/families')
            ->assertStatus(400)
            ->assertJson([
                'message' => 'Admin must select a restaurant context before operating tenant modules.',
            ]);
    }

    public function test_admin_can_select_restaurant_context_and_then_use_tenant_modules(): void
    {
        $restaurantId = $this->createRestaurant('Context Restaurant', 'context@local.test');
        $restaurantUuid = (string) DB::table('restaurants')->where('id', $restaurantId)->value('uuid');
        $adminUuid = (string) Str::uuid();

        DB::table('users')->insert([
            'restaurant_id' => null,
            'uuid' => $adminUuid,
            'role' => 'admin',
            'name' => 'Platform Admin',
            'email' => 'admin-context-select@local.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'auth_user_id' => $adminUuid,
        ])->postJson('/api/admin/context/restaurant', [
            'restaurant_id' => $restaurantUuid,
        ])->assertStatus(200)
            ->assertJson([
                'success' => true,
                'restaurant_id' => $restaurantUuid,
            ]);

        $this->withSession([
            'auth_user_id' => $adminUuid,
            'tenant_restaurant_uuid' => $restaurantUuid,
        ])->getJson('/api/families')
            ->assertStatus(200);
    }

    public function test_operator_can_list_order_lines_by_order_id(): void
    {
        $restaurantId = $this->createRestaurant('Order Lines Restaurant', 'order-lines@local.test');

        $zoneId = DB::table('zones')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'name' => 'Salon',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tableUuid = (string) Str::uuid();
        $tableId = DB::table('tables')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => $tableUuid,
            'zone_id' => $zoneId,
            'name' => 'S1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userUuid = (string) Str::uuid();
        $userId = DB::table('users')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => $userUuid,
            'role' => 'operator',
            'name' => 'Operator User',
            'email' => 'order-lines-operator@local.test',
            'password' => Hash::make('password123'),
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
        $productId = DB::table('products')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => $productUuid,
            'family_id' => $familyId,
            'tax_id' => $taxId,
            'name' => 'Cafe',
            'price' => 150,
            'stock' => 20,
            'active' => true,
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

        DB::table('order_lines')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'order_id' => $orderId,
            'product_id' => $productId,
            'user_id' => $userId,
            'quantity' => 2,
            'price' => 150,
            'tax_percentage' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'auth_user_id' => $userUuid,
        ])->getJson('/api/orders/' . $orderUuid . '/lines')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.order_id', $orderUuid)
            ->assertJsonPath('0.product_id', $productUuid)
            ->assertJsonPath('0.product_name', 'Cafe')
            ->assertJsonPath('0.quantity', 2);
    }

    public function test_admin_restaurants_routes_require_admin_role(): void
    {
        $restaurantId = $this->createRestaurant('Protected Restaurants', 'protected-restaurants@local.test');
        $restaurantUuid = (string) DB::table('restaurants')->where('id', $restaurantId)->value('uuid');

        $operatorUuid = (string) Str::uuid();
        DB::table('users')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => $operatorUuid,
            'role' => 'operator',
            'name' => 'Operator User',
            'email' => 'operator-restaurants@local.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/admin/restaurants')->assertStatus(401);

        $this->withSession([
            'auth_user_id' => $operatorUuid,
        ])->getJson('/api/admin/restaurants')
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden.',
            ]);

        $this->withSession([
            'auth_user_id' => $operatorUuid,
        ])->putJson('/api/admin/restaurants/' . $restaurantUuid, [
            'name' => 'Should Not Update',
        ])->assertStatus(403);
    }

    private function createRestaurant(string $name, string $email): int
    {
        return (int) DB::table('restaurants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'legal_name' => $name . ' S.L.',
            'tax_id' => 'B' . random_int(10000000, 99999999),
            'email' => $email,
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
