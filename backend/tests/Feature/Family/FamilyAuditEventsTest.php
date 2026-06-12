<?php

namespace Tests\Feature\Family;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end check that the Family module leaves an audit trail through the
 * synchronous event bus. Toggling active state is intentionally not audited.
 */
final class FamilyAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_family_writes_a_family_created_audit_log(): void
    {
        $tenant = $this->createTenantSession('admin');

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas'])
            ->assertStatus(201)
            ->json('id');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'family',
            'entity_id' => $uuid,
            'action' => 'family.created',
        ]);
    }

    public function test_updating_a_family_writes_a_family_updated_audit_log(): void
    {
        $tenant = $this->createTenantSession('admin');

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas'])
            ->json('id');

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/families/{$uuid}", ['name' => 'Bebidas Frias'])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'family',
            'entity_id' => $uuid,
            'action' => 'family.updated',
        ]);
    }

    public function test_deleting_a_family_writes_a_family_deleted_audit_log(): void
    {
        $tenant = $this->createTenantSession('admin');

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas'])
            ->json('id');

        $this->withSession($tenant['session'])
            ->deleteJson("/api/admin/families/{$uuid}")
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'family',
            'entity_id' => $uuid,
            'action' => 'family.deleted',
        ]);
    }

    public function test_toggling_active_state_writes_no_audit_log(): void
    {
        $tenant = $this->createTenantSession('admin');

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas'])
            ->json('id');

        $this->withSession($tenant['session'])
            ->patchJson("/api/admin/families/{$uuid}/deactivate")
            ->assertStatus(200);

        $this->assertDatabaseMissing('audit_logs', [
            'entity_id' => $uuid,
            'action' => 'family.deactivated',
        ]);
    }
}
