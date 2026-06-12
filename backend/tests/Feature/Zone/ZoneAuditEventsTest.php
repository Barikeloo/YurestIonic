<?php

namespace Tests\Feature\Zone;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end check that the Zone module leaves an audit trail through the
 * synchronous event bus (entity records events -> use case publishes ->
 * AuditEventSubscriber records).
 */
final class ZoneAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_zone_writes_a_zone_created_audit_log(): void
    {
        $tenant = $this->createTenantSession('admin');

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/zones', ['name' => 'Salon'])
            ->assertStatus(201)
            ->json('id');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'zone',
            'entity_id' => $uuid,
            'action' => 'zone.created',
        ]);
    }

    public function test_updating_a_zone_writes_a_zone_updated_audit_log(): void
    {
        $tenant = $this->createTenantSession('admin');

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/zones', ['name' => 'Salon'])
            ->json('id');

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/zones/{$uuid}", ['name' => 'Terraza'])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'zone',
            'entity_id' => $uuid,
            'action' => 'zone.updated',
        ]);
    }

    public function test_deleting_a_zone_writes_a_zone_deleted_audit_log(): void
    {
        $tenant = $this->createTenantSession('admin');

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/zones', ['name' => 'Salon'])
            ->json('id');

        $this->withSession($tenant['session'])
            ->deleteJson("/api/admin/zones/{$uuid}")
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'zone',
            'entity_id' => $uuid,
            'action' => 'zone.deleted',
        ]);
    }
}
