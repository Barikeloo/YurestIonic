<?php

namespace Tests\Feature\Table;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end check that the Tables module leaves an audit trail through the
 * synchronous event bus, including the group-level merge/unmerge operations.
 */
final class TableAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{tenant: array, zoneId: string} */
    private function tenantWithZone(): array
    {
        $tenant = $this->createTenantSession('admin');
        $zoneId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/zones', ['name' => 'Comedor'])
            ->json('id');

        return ['tenant' => $tenant, 'zoneId' => $zoneId];
    }

    public function test_create_update_delete_write_audit_logs(): void
    {
        ['tenant' => $tenant, 'zoneId' => $zoneId] = $this->tenantWithZone();

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/tables', ['zone_id' => $zoneId, 'name' => 'Mesa 1'])
            ->assertStatus(201)
            ->json('id');

        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'table', 'entity_id' => $uuid, 'action' => 'table.created']);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/tables/{$uuid}", ['zone_id' => $zoneId, 'name' => 'Mesa 1 bis'])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'table', 'entity_id' => $uuid, 'action' => 'table.updated']);

        $this->withSession($tenant['session'])
            ->deleteJson("/api/admin/tables/{$uuid}")
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'table', 'entity_id' => $uuid, 'action' => 'table.deleted']);
    }

    public function test_merge_and_unmerge_write_group_audit_logs(): void
    {
        ['tenant' => $tenant, 'zoneId' => $zoneId] = $this->tenantWithZone();

        $table1 = $this->withSession($tenant['session'])
            ->postJson('/api/admin/tables', ['zone_id' => $zoneId, 'name' => 'Mesa 1'])->json('id');
        $table2 = $this->withSession($tenant['session'])
            ->postJson('/api/admin/tables', ['zone_id' => $zoneId, 'name' => 'Mesa 2'])->json('id');

        $groupId = $this->withSession($tenant['session'])
            ->postJson('/api/admin/tables/merge', ['table_ids' => [$table1, $table2]])
            ->assertStatus(200)
            ->json('group_id');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'table_group',
            'entity_id' => $groupId,
            'action' => 'table.merged',
        ]);

        $this->withSession($tenant['session'])
            ->postJson('/api/admin/tables/unmerge', ['group_id' => $groupId])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'table_group',
            'entity_id' => $groupId,
            'action' => 'table.unmerged',
        ]);
    }
}
