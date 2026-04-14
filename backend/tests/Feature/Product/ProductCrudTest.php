<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_full_crud_flow(): void
    {
        $tenant = $this->createTenantSession();

        $familyResponse = $this->withSession($tenant['session'])->postJson('/api/admin/families', [
            'name' => 'Bebidas',
        ]);

        $familyResponse->assertStatus(201);
        $familyId = $familyResponse->json('id');

        $taxResponse = $this->withSession($tenant['session'])->postJson('/api/admin/taxes', [
            'name' => 'IVA General',
            'percentage' => 21,
        ]);

        $taxResponse->assertStatus(201);
        $taxId = $taxResponse->json('id');

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/admin/products', [
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

        $this->withSession($tenant['session'])->getJson('/api/admin/products')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $productId,
                'name' => 'Coca Cola',
            ]);

        $this->withSession($tenant['session'])->getJson("/api/admin/products/{$productId}")
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

        $secondFamilyResponse = $this->withSession($tenant['session'])->postJson('/api/admin/families', [
            'name' => 'Comida',
        ]);

        $secondFamilyResponse->assertStatus(201);
        $secondFamilyId = $secondFamilyResponse->json('id');

        $secondTaxResponse = $this->withSession($tenant['session'])->postJson('/api/admin/taxes', [
            'name' => 'IVA Reducido',
            'percentage' => 10,
        ]);

        $secondTaxResponse->assertStatus(201);
        $secondTaxId = $secondTaxResponse->json('id');

        $this->withSession($tenant['session'])->putJson("/api/admin/products/{$productId}", [
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

        $this->withSession($tenant['session'])->patchJson("/api/admin/products/{$productId}/activate")
            ->assertStatus(200)
            ->assertJson([
                'id' => $productId,
                'active' => true,
            ]);

        $this->withSession($tenant['session'])->patchJson("/api/admin/products/{$productId}/deactivate")
            ->assertStatus(200)
            ->assertJson([
                'id' => $productId,
                'active' => false,
            ]);

        $this->withSession($tenant['session'])->deleteJson("/api/admin/products/{$productId}")
            ->assertStatus(204);

        $this->withSession($tenant['session'])->getJson("/api/admin/products/{$productId}")
            ->assertStatus(404);
    }
}
