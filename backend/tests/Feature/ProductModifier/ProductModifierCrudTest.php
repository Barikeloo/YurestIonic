<?php

namespace Tests\Feature\ProductModifier;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModifierCrudTest extends TestCase
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
            'name' => 'Coca Cola',
            'price' => 250,
            'stock' => 10,
            'active' => true,
        ]);
        $productResponse->assertStatus(201);
        $this->productId = $productResponse->json('id');
    }

    public function test_modifier_full_crud_flow(): void
    {
        // Create
        $createResponse = $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/modifiers", [
                'name' => 'Extra queso',
                'type' => 'extra',
                'is_required' => false,
                'selection_type' => 'single',
                'price' => 200,
                'active' => true,
                'sort_order' => 1,
            ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson([
            'name' => 'Extra queso',
            'type' => 'extra',
            'is_required' => false,
            'selection_type' => 'single',
            'price' => 200,
            'active' => true,
            'sort_order' => 1,
        ]);
        $createResponse->assertJsonStructure([
            'id', 'product_id', 'created_at', 'updated_at',
        ]);
        $this->assertSame($this->productId, $createResponse->json('product_id'));

        $modifierId = $createResponse->json('id');

        // List
        $this->withSession($this->tenant['session'])
            ->getJson("/api/admin/products/{$this->productId}/modifiers")
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $modifierId,
                'name' => 'Extra queso',
            ]);

        // List empty for different product
        $otherProductId = $this->createAdditionalProduct();
        $this->withSession($this->tenant['session'])
            ->getJson("/api/admin/products/{$otherProductId}/modifiers")
            ->assertStatus(200)
            ->assertJson(['modifiers' => []]);

        // Update
        $this->withSession($this->tenant['session'])
            ->putJson("/api/admin/products/{$this->productId}/modifiers/{$modifierId}", [
                'name' => 'Extra cheddar',
                'type' => 'extra',
                'is_required' => false,
                'selection_type' => 'multi',
                'price' => 350,
                'active' => false,
                'sort_order' => 2,
            ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $modifierId,
                'name' => 'Extra cheddar',
                'price' => 350,
                'active' => false,
                'sort_order' => 2,
            ]);

        // Update validation: extra + required
        $this->withSession($this->tenant['session'])
            ->putJson("/api/admin/products/{$this->productId}/modifiers/{$modifierId}", [
                'name' => 'Extra queso',
                'type' => 'extra',
                'is_required' => true,
                'selection_type' => 'single',
                'price' => 200,
                'active' => true,
                'sort_order' => 1,
            ])
            ->assertStatus(422);

        // Update 404
        $this->withSession($this->tenant['session'])
            ->putJson("/api/admin/products/{$this->productId}/modifiers/" . $this->nonExistentUuid(), [
                'name' => 'Test',
                'type' => 'extra',
                'is_required' => false,
                'selection_type' => 'single',
                'price' => 100,
                'active' => true,
                'sort_order' => 1,
            ])
            ->assertStatus(404);

        // Delete
        $this->withSession($this->tenant['session'])
            ->deleteJson("/api/admin/products/{$this->productId}/modifiers/{$modifierId}")
            ->assertStatus(204);

        // Verify deletion via list
        $this->withSession($this->tenant['session'])
            ->getJson("/api/admin/products/{$this->productId}/modifiers")
            ->assertStatus(200)
            ->assertJson(['modifiers' => []]);

        // Delete 404
        $this->withSession($this->tenant['session'])
            ->deleteJson("/api/admin/products/{$this->productId}/modifiers/{$modifierId}")
            ->assertStatus(404);
    }

    public function test_create_modifier_with_accompaniment_type(): void
    {
        $response = $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/modifiers", [
                'name' => 'Patatas fritas',
                'type' => 'accompaniment',
                'is_required' => true,
                'selection_type' => 'single',
                'price' => 0,
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'name' => 'Patatas fritas',
            'type' => 'accompaniment',
            'is_required' => true,
        ]);
    }

    public function test_create_modifier_validation_errors(): void
    {
        // Missing required fields
        $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/modifiers", [])
            ->assertStatus(422);

        // Invalid type
        $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/modifiers", [
                'name' => 'Test',
                'type' => 'invalid',
                'price' => 100,
            ])
            ->assertStatus(422);

        // Empty name
        $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/modifiers", [
                'name' => '',
                'type' => 'extra',
                'price' => 100,
            ])
            ->assertStatus(422);

        // Negative price
        $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/modifiers", [
                'name' => 'Test',
                'type' => 'extra',
                'price' => -1,
            ])
            ->assertStatus(422);

        // Extra + required
        $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/modifiers", [
                'name' => 'Test',
                'type' => 'extra',
                'is_required' => true,
                'price' => 100,
            ])
            ->assertStatus(422);

        // Product not found
        $this->withSession($this->tenant['session'])
            ->postJson('/api/admin/products/' . $this->nonExistentUuid() . '/modifiers', [
                'name' => 'Test',
                'type' => 'extra',
                'price' => 100,
            ])
            ->assertStatus(404);
    }

    public function test_list_modifiers_product_not_found(): void
    {
        $this->withSession($this->tenant['session'])
            ->getJson('/api/admin/products/' . $this->nonExistentUuid() . '/modifiers')
            ->assertStatus(404);
    }

    public function test_reorder_modifiers(): void
    {
        $modifier1Id = $this->createModifier('Extra queso', 1);
        $modifier2Id = $this->createModifier('Extra cheddar', 2);
        $modifier3Id = $this->createModifier('Patatas fritas', 3);

        $this->withSession($this->tenant['session'])
            ->putJson("/api/admin/products/{$this->productId}/modifiers/reorder", [
                'items' => [
                    ['id' => $modifier3Id, 'sort_order' => 1],
                    ['id' => $modifier1Id, 'sort_order' => 2],
                    ['id' => $modifier2Id, 'sort_order' => 3],
                ],
            ])
            ->assertStatus(204);

        $listResponse = $this->withSession($this->tenant['session'])
            ->getJson("/api/admin/products/{$this->productId}/modifiers")
            ->assertStatus(200);

        $modifiers = $listResponse->json('modifiers');

        $this->assertCount(3, $modifiers);
        $this->assertSame($modifier3Id, $modifiers[0]['id']);
        $this->assertSame($modifier1Id, $modifiers[1]['id']);
        $this->assertSame($modifier2Id, $modifiers[2]['id']);
    }

    public function test_reorder_modifier_not_found(): void
    {
        $this->withSession($this->tenant['session'])
            ->putJson("/api/admin/products/{$this->productId}/modifiers/reorder", [
                'items' => [
                    ['id' => $this->nonExistentUuid(), 'sort_order' => 1],
                ],
            ])
            ->assertStatus(404);
    }

    public function test_reorder_validation_errors(): void
    {
        // Empty items
        $this->withSession($this->tenant['session'])
            ->putJson("/api/admin/products/{$this->productId}/modifiers/reorder", [
                'items' => [],
            ])
            ->assertStatus(422);

        // Missing id
        $this->withSession($this->tenant['session'])
            ->putJson("/api/admin/products/{$this->productId}/modifiers/reorder", [
                'items' => [
                    ['sort_order' => 1],
                ],
            ])
            ->assertStatus(422);
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

    private function createModifier(string $name, int $sortOrder): string
    {
        $response = $this->withSession($this->tenant['session'])
            ->postJson("/api/admin/products/{$this->productId}/modifiers", [
                'name' => $name,
                'type' => 'extra',
                'is_required' => false,
                'selection_type' => 'single',
                'price' => 100,
                'active' => true,
                'sort_order' => $sortOrder,
            ]);
        $response->assertStatus(201);

        return $response->json('id');
    }

    private function nonExistentUuid(): string
    {
        return '00000000-0000-4000-8000-000000000000';
    }
}
