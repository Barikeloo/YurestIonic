<?php

namespace Tests\Feature\ProductModifier;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end check that the ProductModifier module writes an audit trail
 * through the synchronous event bus for create, update and delete operations.
 */
final class ProductModifierAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{tenant: array, productId: string} */
    private function tenantWithProduct(): array
    {
        $tenant = $this->createTenantSession('admin');

        $familyId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas'])->json('id');

        $taxId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/taxes', ['name' => 'IVA General', 'percentage' => 21])->json('id');

        $productId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/products', [
                'family_id' => $familyId,
                'tax_id'    => $taxId,
                'name'      => 'Hamburguesa',
                'price'     => 800,
                'stock'     => 99,
                'active'    => true,
            ])
            ->assertStatus(201)
            ->json('id');

        return compact('tenant', 'productId');
    }

    private function createModifier(array $tenant, string $productId, string $name = 'Extra queso'): string
    {
        return $this->withSession($tenant['session'])
            ->postJson("/api/admin/products/{$productId}/modifiers", [
                'name'           => $name,
                'type'           => 'extra',
                'is_required'    => false,
                'selection_type' => 'single',
                'price'          => 150,
                'active'         => true,
                'sort_order'     => 1,
            ])
            ->assertStatus(201)
            ->json('id');
    }

    public function test_create_writes_audit_log(): void
    {
        ['tenant' => $tenant, 'productId' => $productId] = $this->tenantWithProduct();

        $modifierId = $this->createModifier($tenant, $productId);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product_modifier',
            'entity_id'   => $modifierId,
            'action'      => 'catalog.modifier_created',
        ]);
    }

    public function test_update_writes_audit_log(): void
    {
        ['tenant' => $tenant, 'productId' => $productId] = $this->tenantWithProduct();

        $modifierId = $this->createModifier($tenant, $productId);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/products/{$productId}/modifiers/{$modifierId}", [
                'name'           => 'Extra queso doble',
                'type'           => 'extra',
                'is_required'    => false,
                'selection_type' => 'single',
                'price'          => 250,
                'active'         => true,
                'sort_order'     => 1,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product_modifier',
            'entity_id'   => $modifierId,
            'action'      => 'catalog.modifier_updated',
        ]);
    }

    public function test_delete_writes_audit_log(): void
    {
        ['tenant' => $tenant, 'productId' => $productId] = $this->tenantWithProduct();

        $modifierId = $this->createModifier($tenant, $productId);

        $this->withSession($tenant['session'])
            ->deleteJson("/api/admin/products/{$productId}/modifiers/{$modifierId}")
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product_modifier',
            'entity_id'   => $modifierId,
            'action'      => 'catalog.modifier_deleted',
        ]);
    }
}
