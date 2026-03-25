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

        $zoneResponse = $this->withSession($tenant['session'])->postJson('/api/zones', [
            'name' => 'Comedor',
        ]);

        $zoneResponse->assertStatus(201);

        $zoneId = $zoneResponse->json('id');

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tables', [
            'zone_id' => $zoneId,
            'name' => 'Mesa 1',
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'zone_id' => $zoneId,
            'name' => 'Mesa 1',
        ]);

        $tableId = $createResponse->json('id');

        $this->withSession($tenant['session'])->getJson('/api/tables')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $tableId,
                'zone_id' => $zoneId,
                'name' => 'Mesa 1',
            ]);

        $this->withSession($tenant['session'])->getJson("/api/tables/{$tableId}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $tableId,
                'zone_id' => $zoneId,
                'name' => 'Mesa 1',
            ]);

        $secondZoneResponse = $this->withSession($tenant['session'])->postJson('/api/zones', [
            'name' => 'Terraza',
        ]);

        $secondZoneResponse->assertStatus(201);

        $secondZoneId = $secondZoneResponse->json('id');

        $this->withSession($tenant['session'])->putJson("/api/tables/{$tableId}", [
            'zone_id' => $secondZoneId,
            'name' => 'Mesa 2',
        ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $tableId,
                'zone_id' => $secondZoneId,
                'name' => 'Mesa 2',
            ]);

        $this->withSession($tenant['session'])->deleteJson("/api/tables/{$tableId}")
            ->assertStatus(204);

        $this->withSession($tenant['session'])->getJson("/api/tables/{$tableId}")
            ->assertStatus(404);
    }
}
