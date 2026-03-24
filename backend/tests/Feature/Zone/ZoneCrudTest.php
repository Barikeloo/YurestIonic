<?php

namespace Tests\Feature\Zone;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZoneCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_zone_full_crud_flow(): void
    {
        $createResponse = $this->postJson('/api/zones', [
            'name' => 'Salon principal',
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'name' => 'Salon principal',
        ]);

        $zoneId = $createResponse->json('id');

        $this->getJson('/api/zones')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $zoneId,
                'name' => 'Salon principal',
            ]);

        $this->getJson("/api/zones/{$zoneId}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $zoneId,
                'name' => 'Salon principal',
            ]);

        $this->putJson("/api/zones/{$zoneId}", [
            'name' => 'Terraza exterior',
        ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $zoneId,
                'name' => 'Terraza exterior',
            ]);

        $this->deleteJson("/api/zones/{$zoneId}")
            ->assertStatus(204);

        $this->getJson("/api/zones/{$zoneId}")
            ->assertStatus(404);
    }
}
