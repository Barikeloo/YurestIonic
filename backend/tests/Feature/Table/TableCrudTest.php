<?php

namespace Tests\Feature\Table;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_full_crud_flow(): void
    {
        $tenant = $this->createTenantSession();

        $zoneResponse = $this->withSession($tenant['session'])->postJson('/api/admin/zones', [
            'name' => 'Comedor',
        ]);

        $zoneResponse->assertStatus(201);

        $zoneId = $zoneResponse->json('id');

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/admin/tables', [
            'zone_id' => $zoneId,
            'name' => 'Mesa 1',
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'zone_id' => $zoneId,
            'name' => 'Mesa 1',
        ]);

        $tableId = $createResponse->json('id');

        $this->withSession($tenant['session'])->getJson('/api/admin/tables')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $tableId,
                'zone_id' => $zoneId,
                'name' => 'Mesa 1',
            ]);

        $this->withSession($tenant['session'])->getJson("/api/admin/tables/{$tableId}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $tableId,
                'zone_id' => $zoneId,
                'name' => 'Mesa 1',
            ]);

        $secondZoneResponse = $this->withSession($tenant['session'])->postJson('/api/admin/zones', [
            'name' => 'Terraza',
        ]);

        $secondZoneResponse->assertStatus(201);

        $secondZoneId = $secondZoneResponse->json('id');

        $this->withSession($tenant['session'])->putJson("/api/admin/tables/{$tableId}", [
            'zone_id' => $secondZoneId,
            'name' => 'Mesa 2',
        ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $tableId,
                'zone_id' => $secondZoneId,
                'name' => 'Mesa 2',
            ]);

        $this->withSession($tenant['session'])->deleteJson("/api/admin/tables/{$tableId}")
            ->assertStatus(204);

        $this->withSession($tenant['session'])->getJson("/api/admin/tables/{$tableId}")
            ->assertStatus(404);
    }

    public function test_table_name_can_repeat_in_different_restaurants(): void
    {
        $tenantA = $this->createTenantSession();
        $tenantB = $this->createTenantSession();

        $zoneA = $this->withSession($tenantA['session'])->postJson('/api/admin/zones', [
            'name' => 'Comedor',
        ]);
        $zoneA->assertStatus(201);

        $zoneB = $this->withSession($tenantB['session'])->postJson('/api/admin/zones', [
            'name' => 'Comedor',
        ]);
        $zoneB->assertStatus(201);

        $this->withSession($tenantA['session'])->postJson('/api/admin/tables', [
            'zone_id' => $zoneA->json('id'),
            'name' => 'Mesa 1',
        ])->assertStatus(201);

        $this->withSession($tenantB['session'])->postJson('/api/admin/tables', [
            'zone_id' => $zoneB->json('id'),
            'name' => 'Mesa 1',
        ])->assertStatus(201);
    }

    public function test_table_cannot_be_created_with_zone_from_another_restaurant(): void
    {
        $tenantA = $this->createTenantSession();
        $tenantB = $this->createTenantSession();

        $zoneA = $this->withSession($tenantA['session'])->postJson('/api/admin/zones', [
            'name' => 'Comedor',
        ]);
        $zoneA->assertStatus(201);

        $this->withSession($tenantB['session'])->postJson('/api/admin/tables', [
            'zone_id' => $zoneA->json('id'),
            'name' => 'Mesa Foranea',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['zone_id']);
    }
}
