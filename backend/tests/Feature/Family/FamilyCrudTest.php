<?php

namespace Tests\Feature\Family;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_full_crud_and_activation_flow(): void
    {
        $createResponse = $this->postJson('/api/families', [
            'name' => 'Entrantes',
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'name' => 'Entrantes',
            'active' => true,
        ]);

        $familyId = $createResponse->json('id');

        $this->getJson('/api/families')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $familyId,
                'name' => 'Entrantes',
            ]);

        $this->getJson("/api/families/{$familyId}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $familyId,
                'name' => 'Entrantes',
                'active' => true,
            ]);

        $this->putJson("/api/families/{$familyId}", [
            'name' => 'Entrantes Premium',
        ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $familyId,
                'name' => 'Entrantes Premium',
            ]);

        $this->patchJson("/api/families/{$familyId}/deactivate")
            ->assertStatus(200)
            ->assertJson([
                'id' => $familyId,
                'active' => false,
            ]);

        $this->patchJson("/api/families/{$familyId}/activate")
            ->assertStatus(200)
            ->assertJson([
                'id' => $familyId,
                'active' => true,
            ]);

        $this->deleteJson("/api/families/{$familyId}")
            ->assertStatus(204);

        $this->getJson("/api/families/{$familyId}")
            ->assertStatus(404);
    }
}
