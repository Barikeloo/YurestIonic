<?php

namespace Tests\Feature\Restaurant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RestaurantAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_restaurant_writes_a_restaurant_created_audit_log(): void
    {
        $admin = $this->createSuperAdminSession();

        $uuid = $this->withSession($admin['session'])
            ->postJson('/api/admin/restaurants', [
                'name' => 'Audit Test Restaurant',
                'legal_name' => 'Audit Test S.L.',
                'tax_id' => 'B12345678',
                'email' => 'audit-test@restaurant.local',
                'password' => 'password123',
                'company_mode' => 'new',
            ])
            ->assertStatus(201)
            ->json('id');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'restaurant',
            'entity_id' => $uuid,
            'action' => 'restaurant.created',
        ]);
    }

    public function test_updating_a_restaurant_writes_a_restaurant_updated_audit_log(): void
    {
        $admin = $this->createSuperAdminSession();

        $uuid = $this->withSession($admin['session'])
            ->postJson('/api/admin/restaurants', [
                'name' => 'Update Audit Restaurant',
                'legal_name' => 'Update Audit S.L.',
                'tax_id' => 'B87654321',
                'email' => 'update-audit@restaurant.local',
                'password' => 'password123',
                'company_mode' => 'new',
            ])
            ->assertStatus(201)
            ->json('id');

        $this->withSession($admin['session'])
            ->putJson("/api/admin/restaurants/{$uuid}", ['name' => 'Updated Name'])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'restaurant',
            'entity_id' => $uuid,
            'action' => 'restaurant.updated',
        ]);
    }
}
