<?php

namespace Tests\Feature\Restaurant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRestaurantCollectionKpisTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_restaurant_collection_includes_kpis(): void
    {
        $session = $this->createSuperAdminSession();

        $restaurantUuid = (string) Str::uuid();
        $restaurantId = (int) DB::table('restaurants')->insertGetId([
            'uuid' => $restaurantUuid,
            'name' => 'KPI Restaurant',
            'legal_name' => 'KPI Restaurant S.L.',
            'tax_id' => 'B12345678',
            'email' => 'kpi-'.Str::lower(Str::random(8)).'@example.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'name' => 'User 1',
                'email' => 'user1-'.Str::lower(Str::random(8)).'@example.test',
                'password' => Hash::make('password123'),
                'role' => 'operator',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'restaurant_id' => $restaurantId,
                'uuid' => (string) Str::uuid(),
                'name' => 'User 2',
                'email' => 'user2-'.Str::lower(Str::random(8)).'@example.test',
                'password' => Hash::make('password123'),
                'role' => 'operator',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('zones')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'name' => 'Zona KPI',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('taxes')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA',
            'percentage' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('families')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'name' => 'Familia KPI',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => (string) Str::uuid(),
            'family_id' => (int) DB::table('families')->where('restaurant_id', $restaurantId)->value('id'),
            'tax_id' => (int) DB::table('taxes')->where('restaurant_id', $restaurantId)->value('id'),
            'name' => 'Producto KPI',
            'price' => 1000,
            'stock' => 3,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession($session['session'])->getJson('/api/admin/restaurants');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'uuid' => $restaurantUuid,
                'users' => 2,
                'zones' => 1,
                'products' => 1,
            ]);
    }
}
