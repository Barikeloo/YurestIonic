<?php

namespace Tests\Feature\Table;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_full_crud_flow(): void
    {
        $zoneResponse = $this->postJson('/api/zones', [
            'name' => 'Comedor',
        ]);

        $zoneResponse->assertStatus(201);

        $zoneId = $zoneResponse->json('id');

        $createResponse = $this->postJson('/api/tables', [
            'zone_id' => $zoneId,
            'name' => 'Mesa 1',
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'zone_id' => $zoneId,
            'name' => 'Mesa 1',
        ]);

        $tableId = $createResponse->json('id');

        $this->getJson('/api/tables')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $tableId,
                'zone_id' => $zoneId,
                'name' => 'Mesa 1',
            ]);

        $this->getJson("/api/tables/{$tableId}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $tableId,
                'zone_id' => $zoneId,
                'name' => 'Mesa 1',
            ]);

        $secondZoneResponse = $this->postJson('/api/zones', [
            'name' => 'Terraza',
        ]);

        $secondZoneResponse->assertStatus(201);

        $secondZoneId = $secondZoneResponse->json('id');

        $this->putJson("/api/tables/{$tableId}", [
            'zone_id' => $secondZoneId,
            'name' => 'Mesa 2',
        ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $tableId,
                'zone_id' => $secondZoneId,
                'name' => 'Mesa 2',
            ]);

        $this->deleteJson("/api/tables/{$tableId}")
            ->assertStatus(204);

        $this->getJson("/api/tables/{$tableId}")
            ->assertStatus(404);
    }
}
