<?php

declare(strict_types=1);

namespace Tests\Feature\Table;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZoneLayoutTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ────────────────────────────────────────────────────────────

    private function createZoneAndTable(array $tenant): array
    {
        $zoneId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/zones', ['name' => 'Comedor'])
            ->json('id');

        $tableId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/tables', ['zone_id' => $zoneId, 'name' => 'Mesa 1'])
            ->json('id');

        return compact('zoneId', 'tableId');
    }

    // ── Happy path ─────────────────────────────────────────────────────────

    public function test_save_layout_persists_positions_and_returns_saved_count(): void
    {
        $tenant = $this->createTenantSession('admin');
        ['zoneId' => $zoneId, 'tableId' => $tableId] = $this->createZoneAndTable($tenant);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/zones/{$zoneId}/layout", [
                'tables' => [
                    [
                        'uuid'   => $tableId,
                        'pos_x'  => 100,
                        'pos_y'  => 50,
                        'width'  => 120,
                        'height' => 70,
                        'shape'  => 'rect',
                    ],
                ],
            ])
            ->assertStatus(200)
            ->assertJson(['saved' => 1]);

        $this->assertDatabaseHas('tables', [
            'uuid'   => $tableId,
            'pos_x'  => 100,
            'pos_y'  => 50,
            'width'  => 120,
            'height' => 70,
            'shape'  => 'rect',
        ]);
    }

    public function test_save_layout_updates_existing_position(): void
    {
        $tenant = $this->createTenantSession('admin');
        ['zoneId' => $zoneId, 'tableId' => $tableId] = $this->createZoneAndTable($tenant);

        $payload = fn (int $x) => ['tables' => [['uuid' => $tableId, 'pos_x' => $x, 'pos_y' => 0, 'width' => 80, 'height' => 60, 'shape' => 'rect']]];

        $this->withSession($tenant['session'])->putJson("/api/admin/zones/{$zoneId}/layout", $payload(100));
        $this->withSession($tenant['session'])->putJson("/api/admin/zones/{$zoneId}/layout", $payload(300))
            ->assertStatus(200);

        $this->assertDatabaseHas('tables', ['uuid' => $tableId, 'pos_x' => 300]);
        $this->assertDatabaseMissing('tables', ['uuid' => $tableId, 'pos_x' => 100]);
    }

    public function test_save_layout_with_circle_shape(): void
    {
        $tenant = $this->createTenantSession('admin');
        ['zoneId' => $zoneId, 'tableId' => $tableId] = $this->createZoneAndTable($tenant);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/zones/{$zoneId}/layout", [
                'tables' => [['uuid' => $tableId, 'pos_x' => 0, 'pos_y' => 0, 'width' => 80, 'height' => 80, 'shape' => 'circle']],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('tables', ['uuid' => $tableId, 'shape' => 'circle']);
    }

    public function test_save_layout_silently_skips_table_from_different_zone(): void
    {
        $tenant = $this->createTenantSession('admin');
        ['zoneId' => $zoneId] = $this->createZoneAndTable($tenant);

        // Table belonging to another zone
        $otherZoneId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/zones', ['name' => 'Terraza'])
            ->json('id');
        $otherTableId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/tables', ['zone_id' => $otherZoneId, 'name' => 'T1'])
            ->json('id');

        // Send that table in the layout request for the first zone
        $this->withSession($tenant['session'])
            ->putJson("/api/admin/zones/{$zoneId}/layout", [
                'tables' => [['uuid' => $otherTableId, 'pos_x' => 0, 'pos_y' => 0, 'width' => 80, 'height' => 60, 'shape' => 'rect']],
            ])
            ->assertStatus(200)
            ->assertJson(['saved' => 0]);

        // The other-zone table must remain untouched
        $this->assertDatabaseHas('tables', ['uuid' => $otherTableId, 'pos_x' => null]);
    }

    public function test_save_layout_empty_tables_array_returns_zero(): void
    {
        $tenant = $this->createTenantSession('admin');
        ['zoneId' => $zoneId] = $this->createZoneAndTable($tenant);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/zones/{$zoneId}/layout", ['tables' => []])
            ->assertStatus(200)
            ->assertJson(['saved' => 0]);
    }

    // ── Validation errors ─────────────────────────────────────────────────

    public function test_missing_tables_key_returns_422(): void
    {
        $tenant = $this->createTenantSession('admin');
        ['zoneId' => $zoneId] = $this->createZoneAndTable($tenant);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/zones/{$zoneId}/layout", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tables']);
    }

    public function test_pos_x_out_of_range_returns_422(): void
    {
        $tenant = $this->createTenantSession('admin');
        ['zoneId' => $zoneId, 'tableId' => $tableId] = $this->createZoneAndTable($tenant);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/zones/{$zoneId}/layout", [
                'tables' => [['uuid' => $tableId, 'pos_x' => 1201, 'pos_y' => 0, 'width' => 80, 'height' => 60, 'shape' => 'rect']],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tables.0.pos_x']);
    }

    public function test_invalid_shape_returns_422(): void
    {
        $tenant = $this->createTenantSession('admin');
        ['zoneId' => $zoneId, 'tableId' => $tableId] = $this->createZoneAndTable($tenant);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/zones/{$zoneId}/layout", [
                'tables' => [['uuid' => $tableId, 'pos_x' => 0, 'pos_y' => 0, 'width' => 80, 'height' => 60, 'shape' => 'triangle']],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tables.0.shape']);
    }

    // ── List includes layout ───────────────────────────────────────────────

    public function test_table_list_includes_null_layout_for_unpositioned_table(): void
    {
        $tenant = $this->createTenantSession('admin');
        ['tableId' => $tableId] = $this->createZoneAndTable($tenant);

        $list = $this->withSession($tenant['session'])
            ->getJson('/api/admin/tables')
            ->assertStatus(200)
            ->json();

        $item = collect($list)->firstWhere('id', $tableId);
        $this->assertArrayHasKey('layout', $item);
        $this->assertNull($item['layout']);
    }

    public function test_table_list_includes_layout_after_save(): void
    {
        $tenant = $this->createTenantSession('admin');
        ['zoneId' => $zoneId, 'tableId' => $tableId] = $this->createZoneAndTable($tenant);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/zones/{$zoneId}/layout", [
                'tables' => [['uuid' => $tableId, 'pos_x' => 50, 'pos_y' => 80, 'width' => 100, 'height' => 60, 'shape' => 'rect']],
            ]);

        $list = $this->withSession($tenant['session'])
            ->getJson('/api/admin/tables')
            ->assertStatus(200)
            ->json();

        $item = collect($list)->firstWhere('id', $tableId);
        $this->assertSame(['pos_x' => 50, 'pos_y' => 80, 'width' => 100, 'height' => 60, 'shape' => 'rect'], $item['layout']);
    }

    public function test_tpv_table_list_includes_layout_field(): void
    {
        $tenant = $this->createTenantSession('admin');
        ['zoneId' => $zoneId, 'tableId' => $tableId] = $this->createZoneAndTable($tenant);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/zones/{$zoneId}/layout", [
                'tables' => [['uuid' => $tableId, 'pos_x' => 10, 'pos_y' => 20, 'width' => 80, 'height' => 60, 'shape' => 'circle']],
            ]);

        $list = $this->withSession($tenant['session'])
            ->getJson('/api/tpv/tables')
            ->assertStatus(200)
            ->json();

        $item = collect($list)->firstWhere('id', $tableId);
        $this->assertSame('circle', $item['layout']['shape']);
    }
}
