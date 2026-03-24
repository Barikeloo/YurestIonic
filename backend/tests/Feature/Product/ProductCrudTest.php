<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_full_crud_flow(): void
    {
        $familyResponse = $this->postJson('/api/families', [
            'name' => 'Bebidas',
        ]);

        $familyResponse->assertStatus(201);
        $familyId = $familyResponse->json('id');

        $taxResponse = $this->postJson('/api/taxes', [
            'name' => 'IVA General',
            'percentage' => 21,
        ]);

        $taxResponse->assertStatus(201);
        $taxId = $taxResponse->json('id');

        $createResponse = $this->postJson('/api/products', [
            'family_id' => $familyId,
            'tax_id' => $taxId,
            'image_src' => '/images/coca-cola.png',
            'name' => 'Coca Cola',
            'price' => 250,
            'stock' => 10,
            'active' => true,
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'family_id' => $familyId,
            'tax_id' => $taxId,
            'name' => 'Coca Cola',
            'price' => 250,
            'stock' => 10,
            'active' => true,
        ]);

        $productId = $createResponse->json('id');

        $this->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $productId,
                'name' => 'Coca Cola',
            ]);

        $this->getJson("/api/products/{$productId}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $productId,
                'family_id' => $familyId,
                'tax_id' => $taxId,
                'name' => 'Coca Cola',
                'price' => 250,
                'stock' => 10,
                'active' => true,
            ]);

        $secondFamilyResponse = $this->postJson('/api/families', [
            'name' => 'Comida',
        ]);

        $secondFamilyResponse->assertStatus(201);
        $secondFamilyId = $secondFamilyResponse->json('id');

        $secondTaxResponse = $this->postJson('/api/taxes', [
            'name' => 'IVA Reducido',
            'percentage' => 10,
        ]);

        $secondTaxResponse->assertStatus(201);
        $secondTaxId = $secondTaxResponse->json('id');

        $this->putJson("/api/products/{$productId}", [
            'family_id' => $secondFamilyId,
            'tax_id' => $secondTaxId,
            'image_src' => null,
            'name' => 'Hamburguesa',
            'price' => 850,
            'stock' => 5,
            'active' => false,
        ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $productId,
                'family_id' => $secondFamilyId,
                'tax_id' => $secondTaxId,
                'image_src' => null,
                'name' => 'Hamburguesa',
                'price' => 850,
                'stock' => 5,
                'active' => false,
            ]);

        $this->patchJson("/api/products/{$productId}/activate")
            ->assertStatus(200)
            ->assertJson([
                'id' => $productId,
                'active' => true,
            ]);

        $this->patchJson("/api/products/{$productId}/deactivate")
            ->assertStatus(200)
            ->assertJson([
                'id' => $productId,
                'active' => false,
            ]);

        $this->deleteJson("/api/products/{$productId}")
            ->assertStatus(204);

        $this->getJson("/api/products/{$productId}")
            ->assertStatus(404);
    }
}
