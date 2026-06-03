<?php

namespace Tests\Feature\Audit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditListEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_non_archived_by_default(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        $activeUuid = $this->insertAuditLog($restaurantId, '2026-05-01 10:00:00', null);
        $this->insertAuditLog($restaurantId, '2025-01-01 10:00:00', '2026-06-01 10:00:00');

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.uuid', $activeUuid);
    }

    public function test_admin_can_include_archived_events(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, '2026-05-01 10:00:00', null);
        $this->insertAuditLog($restaurantId, '2025-01-01 10:00:00', '2026-06-01 10:00:00');

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log?include_archived=1');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_non_admin_with_include_archived_returns_403(): void
    {
        $tenant = $this->createTenantSession('operator');
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, '2025-01-01 10:00:00', '2026-06-01 10:00:00');

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log?include_archived=1');

        $response->assertStatus(403);
    }

    private function insertAuditLog(int $restaurantId, string $createdAt, ?string $archivedAt): string
    {
        $uuid = (string) Str::uuid();

        DB::table('audit_logs')->insert([
            'uuid' => $uuid,
            'restaurant_id' => $restaurantId,
            'entity_type' => 'test',
            'entity_id' => (string) Str::uuid(),
            'action' => 'test.event',
            'category' => 'system',
            'severity' => 'info',
            'summary' => 'Test event',
            'reason' => null,
            'session_id' => null,
            'anomaly_kind' => null,
            'integrity_hash' => str_repeat('0', 64),
            'prev_hash' => null,
            'metadata' => json_encode([]),
            'user_id' => null,
            'before' => null,
            'after' => null,
            'ip_address' => null,
            'device_id' => null,
            'created_at' => $createdAt,
            'archived_at' => $archivedAt,
        ]);

        return $uuid;
    }
}
