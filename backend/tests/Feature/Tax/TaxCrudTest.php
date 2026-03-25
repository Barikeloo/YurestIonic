<?php

namespace Tests\Feature\Tax;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_full_crud_flow(): void
    {
        $tenant = $this->createTenantSession();

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/taxes', [
            'name' => 'IVA Intermedio',
            'percentage' => 15,
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'name' => 'IVA Intermedio',
            'percentage' => 15,
        ]);

        $taxId = $createResponse->json('id');

        $this->withSession($tenant['session'])->getJson('/api/taxes')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $taxId,
                'name' => 'IVA Intermedio',
            ]);

        $this->withSession($tenant['session'])->getJson("/api/taxes/{$taxId}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $taxId,
                'name' => 'IVA Intermedio',
                'percentage' => 15,
            ]);

        $this->withSession($tenant['session'])->putJson("/api/taxes/{$taxId}", [
            'name' => 'IVA Intermedio Revisado',
            'percentage' => 16,
        ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $taxId,
                'name' => 'IVA Intermedio Revisado',
                'percentage' => 16,
            ]);

        $this->withSession($tenant['session'])->deleteJson("/api/taxes/{$taxId}")
            ->assertStatus(204);

        $this->withSession($tenant['session'])->getJson("/api/taxes/{$taxId}")
            ->assertStatus(404);
    }
}
