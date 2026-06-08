<?php

namespace Tests\Feature\ProductVariant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantCrudTest extends TestCase
{
    use RefreshDatabase;

    private array $tenant;
    private string $productId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = $this->createTenantSession('admin');

        $familyResponse = $this->withSession($this->tenant['session'])->postJson('/api/admin/families', [
            'name' => 'Bebidas',
        ]);
        $familyId = $familyResponse->json('id');

        $taxResponse = $this->withSession($this->tenant['session'])->postJson('/api/admin/taxes', [
            'name' => 'IVA General',
            'percentage' => 21,
        ]);
        $taxId = $taxResponse->json('id');

        $productResponse = $this->withSession($this->tenant['session'])->postJson('/api/admin/products', [
            'family_id' => $familyId,
            'tax_id' => $taxId,
            'name' => 'Camiseta',
            'price' => 2500,
            'stock' => 100,
            'active' => true,
        ]);
        $productResponse->assertStatus(201);
        $this->productId = $productResponse->json('id');
    }

    public function test_variant_full_crud_flow(): void
    {

        $createResponse = $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/variants", [
                'name' => 'Rojo',
                'price' => 1500,
                'stock' => 10,
                'active' => true,
                'sort_order' => 1,
            ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'name' => 'Rojo',
            'price' => 1500,
            'stock' => 10,
            'active' => true,
            'sort_order' => 1,
        ]);
        $createResponse->assertJsonStructure([
            'id', 'product_id', 'created_at', 'updated_at',
        ]);
        $this->assertSame($this->productId, $createResponse->json('product_id'));

        $variantId = $createResponse->json('id');

        $this->withSession($this->tenant['session'])
            ->getJson("/api/admin/products/{$this->productId}/variants")
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $variantId,
                'name' => 'Rojo',
            ]);

        $otherProductId = $this->createAdditionalProduct();
        $this->withSession($this->tenant['session'])
            ->getJson("/api/admin/products/{$otherProductId}/variants")
            ->assertStatus(200)
            ->assertJson(['variants' => []]);

        $this->withSession($this->tenant['session'])
            ->putJson("/api/admin/products/{$this->productId}/variants/{$variantId}", [
                'name' => 'Azul',
                'price' => 2000,
                'stock' => 20,
                'active' => false,
                'sort_order' => 2,
            ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $variantId,
                'name' => 'Azul',
                'price' => 2000,
                'stock' => 20,
                'active' => false,
                'sort_order' => 2,
            ]);

        $this->withSession($this->tenant['session'])
            ->putJson("/api/admin/products/{$this->productId}/variants/" . $this->nonExistentUuid(), [
                'name' => 'Test',
                'price' => 100,
                'stock' => 5,
                'active' => true,
                'sort_order' => 1,
            ])
            ->assertStatus(404);

        $this->withSession($this->tenant['session'])
            ->deleteJson("/api/admin/products/{$this->productId}/variants/{$variantId}")
            ->assertStatus(204);

        $this->withSession($this->tenant['session'])
            ->getJson("/api/admin/products/{$this->productId}/variants")
            ->assertStatus(200)
            ->assertJson(['variants' => []]);

        $this->withSession($this->tenant['session'])
            ->deleteJson("/api/admin/products/{$this->productId}/variants/{$variantId}")
            ->assertStatus(404);
    }

    public function test_create_variant_validation_errors(): void
    {

        $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/variants", [])
            ->assertStatus(422);

        $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/variants", [
                'name' => '',
                'price' => 100,
                'stock' => 5,
            ])
            ->assertStatus(422);

        $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/variants", [
                'name' => 'Test',
                'price' => -1,
                'stock' => 5,
            ])
            ->assertStatus(422);

        $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/variants", [
                'name' => 'Test',
                'price' => 100,
                'stock' => -1,
            ])
            ->assertStatus(422);

        $this->withSession($this->tenant['session'])
            ->postJson('/api/admin/products/' . $this->nonExistentUuid() . '/variants', [
                'name' => 'Test',
                'price' => 100,
                'stock' => 5,
            ])
            ->assertStatus(404);
    }

    public function test_list_variants_product_not_found(): void
    {
        $this->withSession($this->tenant['session'])
            ->getJson('/api/admin/products/' . $this->nonExistentUuid() . '/variants')
            ->assertStatus(404);
    }

    private function createAdditionalProduct(): string
    {
        $familyResponse = $this->withSession($this->tenant['session'])->postJson('/api/admin/families', [
            'name' => 'Comida',
        ]);
        $familyId = $familyResponse->json('id');

        $taxResponse = $this->withSession($this->tenant['session'])->postJson('/api/admin/taxes', [
            'name' => 'IVA Reducido',
            'percentage' => 10,
        ]);
        $taxId = $taxResponse->json('id');

        $response = $this->withSession($this->tenant['session'])->postJson('/api/admin/products', [
            'family_id' => $familyId,
            'tax_id' => $taxId,
            'name' => 'Hamburguesa',
            'price' => 850,
            'stock' => 5,
            'active' => true,
        ]);
        $response->assertStatus(201);

        return $response->json('id');
    }

    private function nonExistentUuid(): string
    {
        return '00000000-0000-4000-8000-000000000000';
    }
}
