<?php

declare(strict_types=1);

namespace Tests\Feature\Menu;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MenuAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{tenant: array, productId: string, taxId: string} */
    private function tenantWithProduct(): array
    {
        $tenant = $this->createTenantSession('admin');

        $familyId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Comidas'])
            ->json('id');

        $taxId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/taxes', ['name' => 'IVA General', 'percentage' => 21])
            ->json('id');

        $productId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/products', [
                'family_id' => $familyId,
                'tax_id' => $taxId,
                'name' => 'Producto Test',
                'price' => 1000,
                'stock' => 10,
                'active' => true,
            ])
            ->assertStatus(201)
            ->json('id');

        return compact('tenant', 'productId', 'taxId');
    }

    public function test_creating_a_menu_writes_menu_created_audit_log(): void
    {
        ['tenant' => $tenant, 'productId' => $productId, 'taxId' => $taxId] = $this->tenantWithProduct();

        $menuUuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/menus', [
                'tax_id' => $taxId,
                'name' => 'Menú del día',
                'description' => 'Descripción',
                'price' => 1500,
                'available_days' => [1, 2, 3, 4, 5, 6, 7],
                'active' => true,
                'sections' => [
                    [
                        'name' => 'Primero',
                        'min_choices' => 1,
                        'max_choices' => 1,
                        'items' => [
                            ['product_id' => $productId, 'extra_price' => 0],
                        ],
                    ],
                ],
            ])
            ->assertStatus(201)
            ->json('id');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'menu',
            'entity_id' => $menuUuid,
            'action' => 'menu.created',
        ]);
    }

    public function test_updating_a_menu_writes_menu_updated_audit_log(): void
    {
        ['tenant' => $tenant, 'productId' => $productId, 'taxId' => $taxId] = $this->tenantWithProduct();

        $menuUuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/menus', [
                'tax_id' => $taxId,
                'name' => 'Menú original',
                'price' => 1500,
                'available_days' => [1, 2, 3, 4, 5, 6, 7],
                'active' => true,
                'sections' => [
                    [
                        'name' => 'Único',
                        'min_choices' => 1,
                        'max_choices' => 1,
                        'items' => [
                            ['product_id' => $productId, 'extra_price' => 0],
                        ],
                    ],
                ],
            ])
            ->assertStatus(201)
            ->json('id');

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/menus/{$menuUuid}", [
                'tax_id' => $taxId,
                'name' => 'Menú actualizado',
                'price' => 2000,
                'available_days' => [1, 2, 3, 4, 5, 6, 7],
                'active' => true,
                'sections' => [
                    [
                        'name' => 'Único',
                        'min_choices' => 1,
                        'max_choices' => 1,
                        'items' => [
                            ['product_id' => $productId, 'extra_price' => 0],
                        ],
                    ],
                ],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'menu',
            'entity_id' => $menuUuid,
            'action' => 'menu.updated',
        ]);
    }

    public function test_archiving_a_menu_writes_menu_archived_audit_log(): void
    {
        ['tenant' => $tenant, 'productId' => $productId, 'taxId' => $taxId] = $this->tenantWithProduct();

        $menuUuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/menus', [
                'tax_id' => $taxId,
                'name' => 'Menú a archivar',
                'price' => 1000,
                'available_days' => [1, 2, 3, 4, 5, 6, 7],
                'active' => true,
                'sections' => [
                    [
                        'name' => 'Único',
                        'min_choices' => 1,
                        'max_choices' => 1,
                        'items' => [
                            ['product_id' => $productId, 'extra_price' => 0],
                        ],
                    ],
                ],
            ])
            ->assertStatus(201)
            ->json('id');

        $this->withSession($tenant['session'])
            ->deleteJson("/api/admin/menus/{$menuUuid}")
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'menu',
            'entity_id' => $menuUuid,
            'action' => 'menu.archived',
        ]);
    }

    public function test_activating_a_menu_writes_menu_activated_audit_log(): void
    {
        ['tenant' => $tenant, 'productId' => $productId, 'taxId' => $taxId] = $this->tenantWithProduct();

        $menuUuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/menus', [
                'tax_id' => $taxId,
                'name' => 'Menú a activar',
                'price' => 1000,
                'available_days' => [1, 2, 3, 4, 5, 6, 7],
                'active' => false,
                'sections' => [
                    [
                        'name' => 'Único',
                        'min_choices' => 1,
                        'max_choices' => 1,
                        'items' => [
                            ['product_id' => $productId, 'extra_price' => 0],
                        ],
                    ],
                ],
            ])
            ->assertStatus(201)
            ->json('id');

        $this->withSession($tenant['session'])
            ->patchJson("/api/admin/menus/{$menuUuid}/activate")
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'menu',
            'entity_id' => $menuUuid,
            'action' => 'menu.activated',
        ]);
    }

    public function test_deactivating_a_menu_writes_menu_deactivated_audit_log(): void
    {
        ['tenant' => $tenant, 'productId' => $productId, 'taxId' => $taxId] = $this->tenantWithProduct();

        $menuUuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/menus', [
                'tax_id' => $taxId,
                'name' => 'Menú a desactivar',
                'price' => 1000,
                'available_days' => [1, 2, 3, 4, 5, 6, 7],
                'active' => true,
                'sections' => [
                    [
                        'name' => 'Único',
                        'min_choices' => 1,
                        'max_choices' => 1,
                        'items' => [
                            ['product_id' => $productId, 'extra_price' => 0],
                        ],
                    ],
                ],
            ])
            ->assertStatus(201)
            ->json('id');

        $this->withSession($tenant['session'])
            ->patchJson("/api/admin/menus/{$menuUuid}/deactivate")
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'menu',
            'entity_id' => $menuUuid,
            'action' => 'menu.deactivated',
        ]);
    }
}
