<?php

namespace Tests\Feature\Family;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_full_crud_and_activation_flow(): void
    {
        $tenant = $this->createTenantSession();

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/admin/families', [
            'name' => 'Entrantes',
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'name' => 'Entrantes',
            'active' => true,
        ]);

        $familyId = $createResponse->json('id');

        $this->withSession($tenant['session'])->getJson('/api/admin/families')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $familyId,
                'name' => 'Entrantes',
            ]);

        $this->withSession($tenant['session'])->getJson("/api/admin/families/{$familyId}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $familyId,
                'name' => 'Entrantes',
                'active' => true,
            ]);

        $this->withSession($tenant['session'])->putJson("/api/admin/families/{$familyId}", [
            'name' => 'Entrantes Premium',
        ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $familyId,
                'name' => 'Entrantes Premium',
            ]);

        $this->withSession($tenant['session'])->patchJson("/api/admin/families/{$familyId}/deactivate")
            ->assertStatus(200)
            ->assertJson([
                'id' => $familyId,
                'active' => false,
            ]);

        $this->withSession($tenant['session'])->patchJson("/api/admin/families/{$familyId}/activate")
            ->assertStatus(200)
            ->assertJson([
                'id' => $familyId,
                'active' => true,
            ]);

        $this->withSession($tenant['session'])->deleteJson("/api/admin/families/{$familyId}")
            ->assertStatus(204);

        $this->withSession($tenant['session'])->getJson("/api/admin/families/{$familyId}")
            ->assertStatus(404);
    }
}
