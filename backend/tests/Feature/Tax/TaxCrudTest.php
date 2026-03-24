<?php

namespace Tests\Feature\Tax;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_full_crud_flow(): void
    {
        $createResponse = $this->postJson('/api/taxes', [
            'name' => 'IVA Intermedio',
            'percentage' => 15,
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'name' => 'IVA Intermedio',
            'percentage' => 15,
        ]);

        $taxId = $createResponse->json('id');

        $this->getJson('/api/taxes')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $taxId,
                'name' => 'IVA Intermedio',
            ]);

        $this->getJson("/api/taxes/{$taxId}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $taxId,
                'name' => 'IVA Intermedio',
                'percentage' => 15,
            ]);

        $this->putJson("/api/taxes/{$taxId}", [
            'name' => 'IVA Intermedio Revisado',
            'percentage' => 16,
        ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $taxId,
                'name' => 'IVA Intermedio Revisado',
                'percentage' => 16,
            ]);

        $this->deleteJson("/api/taxes/{$taxId}")
            ->assertStatus(204);

        $this->getJson("/api/taxes/{$taxId}")
            ->assertStatus(404);
    }
}
