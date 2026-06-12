<?php

namespace Tests\Feature\Tax;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end check that the Tax module still leaves an audit trail now that
 * auditing goes through the synchronous event bus (entity records events ->
 * use case publishes -> AuditEventSubscriber records) instead of a direct
 * AuditRecorder call.
 */
final class TaxAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_tax_writes_a_tax_created_audit_log(): void
    {
        $tenant = $this->createTenantSession('admin');

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/taxes', ['name' => 'IVA Test', 'percentage' => 21])
            ->assertStatus(201)
            ->json('id');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'tax',
            'entity_id' => $uuid,
            'action' => 'tax.created',
        ]);
    }

    public function test_updating_a_tax_writes_a_tax_updated_audit_log(): void
    {
        $tenant = $this->createTenantSession('admin');

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/taxes', ['name' => 'IVA Test', 'percentage' => 21])
            ->json('id');

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/taxes/{$uuid}", ['percentage' => 10])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'tax',
            'entity_id' => $uuid,
            'action' => 'tax.updated',
        ]);
    }

    public function test_unchanged_update_writes_no_audit_log(): void
    {
        $tenant = $this->createTenantSession('admin');

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/taxes', ['name' => 'IVA Test', 'percentage' => 21])
            ->json('id');

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/taxes/{$uuid}", ['percentage' => 21])
            ->assertStatus(200);

        $this->assertDatabaseMissing('audit_logs', [
            'entity_id' => $uuid,
            'action' => 'tax.updated',
        ]);
    }

    public function test_deleting_a_tax_writes_a_tax_deleted_audit_log(): void
    {
        $tenant = $this->createTenantSession('admin');

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/taxes', ['name' => 'IVA Test', 'percentage' => 21])
            ->json('id');

        $this->withSession($tenant['session'])
            ->deleteJson("/api/admin/taxes/{$uuid}")
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'tax',
            'entity_id' => $uuid,
            'action' => 'tax.deleted',
        ]);
    }
}
