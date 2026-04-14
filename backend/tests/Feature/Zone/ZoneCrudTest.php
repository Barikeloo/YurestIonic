<?php

namespace Tests\Feature\Zone;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZoneCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_zone_full_crud_flow(): void
    {
        $tenant = $this->createTenantSession();

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/admin/zones', [
            'name' => 'Salon principal',
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'name' => 'Salon principal',
        ]);

        $zoneId = $createResponse->json('id');

        $this->withSession($tenant['session'])->getJson('/api/admin/zones')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $zoneId,
                'name' => 'Salon principal',
            ]);

        $this->withSession($tenant['session'])->getJson("/api/admin/zones/{$zoneId}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $zoneId,
                'name' => 'Salon principal',
            ]);

        $this->withSession($tenant['session'])->putJson("/api/admin/zones/{$zoneId}", [
            'name' => 'Terraza exterior',
        ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $zoneId,
                'name' => 'Terraza exterior',
            ]);

        $this->withSession($tenant['session'])->deleteJson("/api/admin/zones/{$zoneId}")
            ->assertStatus(204);

        $this->withSession($tenant['session'])->getJson("/api/admin/zones/{$zoneId}")
            ->assertStatus(404);
    }

    public function test_zone_name_uniqueness_is_scoped_per_restaurant(): void
    {
        $tenantA = $this->createTenantSession();
        $tenantB = $this->createTenantSession();

        $this->withSession($tenantA['session'])->postJson('/api/admin/zones', [
            'name' => 'Casitas',
        ])->assertStatus(201);

        $this->withSession($tenantB['session'])->postJson('/api/admin/zones', [
            'name' => 'Casitas',
        ])->assertStatus(201);
    }
}
