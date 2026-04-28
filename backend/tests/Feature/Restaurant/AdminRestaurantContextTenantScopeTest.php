<?php

namespace Tests\Feature\Restaurant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRestaurantContextTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_selected_restaurant_context_is_used_for_tenant_modules(): void
    {
        $tenant = $this->createTenantSession('admin');

        $linkedRestaurant = DB::table('restaurants')->where('id', $tenant['restaurant_id'])->first();
        $this->assertNotNull($linkedRestaurant);

        $secondRestaurantUuid = (string) Str::uuid();
        $secondRestaurantId = (int) DB::table('restaurants')->insertGetId([
            'uuid' => $secondRestaurantUuid,
            'name' => 'Second Restaurant',
            'legal_name' => 'Second Restaurant S.L.',
            'tax_id' => $linkedRestaurant->tax_id,
            'email' => 'second-'.Str::lower(Str::random(8)).'@local.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession($tenant['session'])
            ->postJson('/api/admin/context/restaurant', [
                'restaurant_id' => $secondRestaurantUuid,
            ])
            ->assertStatus(200);

        $zoneResponse = $this->withSession($tenant['session'])
            ->postJson('/api/admin/zones', [
                'name' => 'Zona Contexto B',
            ])
            ->assertStatus(201);

        $zoneUuid = $zoneResponse->json('id');

        $zoneRestaurantId = DB::table('zones')->where('uuid', $zoneUuid)->value('restaurant_id');
        $this->assertSame($secondRestaurantId, (int) $zoneRestaurantId);

        $this->withSession($tenant['session'])
            ->postJson('/api/admin/context/restaurant', [
                'restaurant_id' => $tenant['restaurant_uuid'],
            ])
            ->assertStatus(200);

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/zones')
            ->assertStatus(200)
            ->assertJsonMissing([
                'id' => $zoneUuid,
            ]);
    }
}
