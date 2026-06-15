<?php

namespace Tests\Feature\ProductVariant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductVariantAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{tenant: array, productId: string} */
    private function tenantWithProduct(): array
    {
        $tenant = $this->createTenantSession('admin');

        $familyId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Ropa'])->json('id');

        $taxId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/taxes', ['name' => 'IVA General', 'percentage' => 21])->json('id');

        $productId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/products', [
                'family_id' => $familyId,
                'tax_id'    => $taxId,
                'name'      => 'Camiseta',
                'price'     => 2500,
                'stock'     => 100,
                'active'    => true,
            ])
            ->assertStatus(201)
            ->json('id');

        return compact('tenant', 'productId');
    }

    private function createVariant(array $tenant, string $productId, string $name = 'Rojo'): string
    {
        return $this->withSession($tenant['session'])
            ->postJson("/api/admin/products/{$productId}/variants", [
                'name'       => $name,
                'price'      => 1500,
                'stock'      => 10,
                'active'     => true,
                'sort_order' => 1,
            ])
            ->assertStatus(201)
            ->json('id');
    }

    public function test_create_writes_audit_log(): void
    {
        ['tenant' => $tenant, 'productId' => $productId] = $this->tenantWithProduct();

        $variantId = $this->createVariant($tenant, $productId);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product_variant',
            'entity_id'   => $variantId,
            'action'      => 'catalog.variant_created',
        ]);
    }

    public function test_update_writes_audit_log(): void
    {
        ['tenant' => $tenant, 'productId' => $productId] = $this->tenantWithProduct();

        $variantId = $this->createVariant($tenant, $productId);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/products/{$productId}/variants/{$variantId}", [
                'name'       => 'Azul',
                'price'      => 2000,
                'stock'      => 20,
                'active'     => false,
                'sort_order' => 2,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product_variant',
            'entity_id'   => $variantId,
            'action'      => 'catalog.variant_updated',
        ]);
    }

    public function test_delete_writes_audit_log(): void
    {
        ['tenant' => $tenant, 'productId' => $productId] = $this->tenantWithProduct();

        $variantId = $this->createVariant($tenant, $productId);

        $this->withSession($tenant['session'])
            ->deleteJson("/api/admin/products/{$productId}/variants/{$variantId}")
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product_variant',
            'entity_id'   => $variantId,
            'action'      => 'catalog.variant_deleted',
        ]);
    }
}
