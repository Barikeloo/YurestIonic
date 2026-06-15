<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end check that the Product module leaves an audit trail through the
 * synchronous event bus for all mutating operations.
 */
final class ProductAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{tenant: array, familyId: string, taxId: string} */
    private function tenantWithDeps(): array
    {
        $tenant = $this->createTenantSession('admin');

        $familyId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas'])
            ->json('id');

        $taxId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/taxes', ['name' => 'IVA General', 'percentage' => 21])
            ->json('id');

        return compact('tenant', 'familyId', 'taxId');
    }

    private function createProduct(array $tenant, string $familyId, string $taxId, string $name = 'Coca Cola'): string
    {
        return $this->withSession($tenant['session'])
            ->postJson('/api/admin/products', [
                'family_id' => $familyId,
                'tax_id'    => $taxId,
                'name'      => $name,
                'price'     => 250,
                'stock'     => 10,
                'active'    => true,
            ])
            ->assertStatus(201)
            ->json('id');
    }

    public function test_create_writes_audit_log(): void
    {
        ['tenant' => $tenant, 'familyId' => $familyId, 'taxId' => $taxId] = $this->tenantWithDeps();

        $id = $this->createProduct($tenant, $familyId, $taxId);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product',
            'entity_id'   => $id,
            'action'      => 'product.created',
        ]);
    }

    public function test_update_writes_audit_log(): void
    {
        ['tenant' => $tenant, 'familyId' => $familyId, 'taxId' => $taxId] = $this->tenantWithDeps();

        $id = $this->createProduct($tenant, $familyId, $taxId);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/products/{$id}", [
                'family_id' => $familyId,
                'tax_id'    => $taxId,
                'name'      => 'Coca Cola Zero',
                'price'     => 250,
                'stock'     => 10,
                'active'    => true,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product',
            'entity_id'   => $id,
            'action'      => 'product.updated',
        ]);
    }

    public function test_update_with_price_change_writes_two_audit_logs(): void
    {
        ['tenant' => $tenant, 'familyId' => $familyId, 'taxId' => $taxId] = $this->tenantWithDeps();

        $id = $this->createProduct($tenant, $familyId, $taxId);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/products/{$id}", [
                'family_id' => $familyId,
                'tax_id'    => $taxId,
                'name'      => 'Coca Cola',
                'price'     => 350,
                'stock'     => 10,
                'active'    => true,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product',
            'entity_id'   => $id,
            'action'      => 'product.updated',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product',
            'entity_id'   => $id,
            'action'      => 'product.price_changed',
        ]);
    }

    public function test_delete_writes_audit_log(): void
    {
        ['tenant' => $tenant, 'familyId' => $familyId, 'taxId' => $taxId] = $this->tenantWithDeps();

        $id = $this->createProduct($tenant, $familyId, $taxId);

        $this->withSession($tenant['session'])
            ->deleteJson("/api/admin/products/{$id}")
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product',
            'entity_id'   => $id,
            'action'      => 'product.deleted',
        ]);
    }

    public function test_activate_writes_audit_log(): void
    {
        ['tenant' => $tenant, 'familyId' => $familyId, 'taxId' => $taxId] = $this->tenantWithDeps();

        $id = $this->createProduct($tenant, $familyId, $taxId);

        // Deactivate first so we can activate
        $this->withSession($tenant['session'])
            ->patchJson("/api/admin/products/{$id}/deactivate")
            ->assertStatus(200);

        $this->withSession($tenant['session'])
            ->patchJson("/api/admin/products/{$id}/activate")
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product',
            'entity_id'   => $id,
            'action'      => 'product.activated',
        ]);
    }

    public function test_deactivate_writes_audit_log(): void
    {
        ['tenant' => $tenant, 'familyId' => $familyId, 'taxId' => $taxId] = $this->tenantWithDeps();

        $id = $this->createProduct($tenant, $familyId, $taxId);

        $this->withSession($tenant['session'])
            ->patchJson("/api/admin/products/{$id}/deactivate")
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'product',
            'entity_id'   => $id,
            'action'      => 'product.deactivated',
        ]);
    }
}
