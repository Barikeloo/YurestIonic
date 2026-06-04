<?php

namespace Tests\Feature\Audit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditArchivedStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_empty_stats_when_no_rows_are_archived(): void
    {
        $tenant = $this->createTenantSession('admin');
        $this->insertAuditLog($tenant['restaurant_id'], '2026-05-01 10:00:00', null);

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats');

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 0,
            'oldest_created_at' => null,
            'newest_created_at' => null,
            'monthly_breakdown' => [],
        ]);
    }

    public function test_returns_total_range_and_monthly_breakdown_of_archived_rows(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, '2025-01-15 09:00:00', '2026-06-01 10:00:00');
        $this->insertAuditLog($restaurantId, '2025-01-20 11:00:00', '2026-06-01 10:00:00');
        $this->insertAuditLog($restaurantId, '2025-03-05 12:00:00', '2026-06-01 10:00:00');
        $this->insertAuditLog($restaurantId, '2026-05-01 10:00:00', null);

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats');

        $response->assertStatus(200);
        $response->assertJsonPath('total', 3);
        $response->assertJsonPath('oldest_created_at', '2025-01-15T09:00:00+00:00');
        $response->assertJsonPath('newest_created_at', '2025-03-05T12:00:00+00:00');
        $response->assertJsonPath('monthly_breakdown', [
            ['month' => '2025-01', 'count' => 2],
            ['month' => '2025-03', 'count' => 1],
        ]);
    }

    public function test_scope_is_per_restaurant(): void
    {
        $tenantA = $this->createTenantSession('admin');
        $tenantB = $this->createTenantSession('admin');

        $this->insertAuditLog($tenantA['restaurant_id'], '2025-01-15 09:00:00', '2026-06-01 10:00:00');
        $this->insertAuditLog($tenantB['restaurant_id'], '2025-02-20 09:00:00', '2026-06-01 10:00:00');
        $this->insertAuditLog($tenantB['restaurant_id'], '2025-02-21 09:00:00', '2026-06-01 10:00:00');

        $responseA = $this
            ->withSession($tenantA['session'])
            ->getJson('/api/admin/audit-log/archived-stats');
        $responseA->assertStatus(200);
        $responseA->assertJsonPath('total', 1);

        $responseB = $this
            ->withSession($tenantB['session'])
            ->getJson('/api/admin/audit-log/archived-stats');
        $responseB->assertStatus(200);
        $responseB->assertJsonPath('total', 2);
    }

    public function test_filters_by_date_from(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, '2025-01-15 09:00:00', '2026-06-01 10:00:00');
        $this->insertAuditLog($restaurantId, '2025-03-05 12:00:00', '2026-06-01 10:00:00');
        $this->insertAuditLog($restaurantId, '2025-07-10 12:00:00', '2026-06-01 10:00:00');

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats?date_from=2025-03-01');

        $response->assertStatus(200);
        $response->assertJsonPath('total', 2);
        $response->assertJsonPath('monthly_breakdown', [
            ['month' => '2025-03', 'count' => 1],
            ['month' => '2025-07', 'count' => 1],
        ]);
    }

    public function test_filters_by_date_from_and_date_to(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, '2025-01-15 09:00:00', '2026-06-01 10:00:00');
        $this->insertAuditLog($restaurantId, '2025-03-05 12:00:00', '2026-06-01 10:00:00');
        $this->insertAuditLog($restaurantId, '2025-03-25 18:00:00', '2026-06-01 10:00:00');
        $this->insertAuditLog($restaurantId, '2025-07-10 12:00:00', '2026-06-01 10:00:00');

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats?date_from=2025-03-01&date_to=2025-03-31');

        $response->assertStatus(200);
        $response->assertJsonPath('total', 2);
        $response->assertJsonPath('oldest_created_at', '2025-03-05T12:00:00+00:00');
        $response->assertJsonPath('newest_created_at', '2025-03-25T18:00:00+00:00');
        $response->assertJsonPath('monthly_breakdown', [
            ['month' => '2025-03', 'count' => 2],
        ]);
    }

    public function test_rejects_inverted_date_range(): void
    {
        $tenant = $this->createTenantSession('admin');

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats?date_from=2025-07-01&date_to=2025-01-01');

        $response->assertStatus(422);
    }

    public function test_non_admin_is_rejected_by_middleware(): void
    {
        $tenant = $this->createTenantSession('operator');

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats');

        $response->assertStatus(403);
    }

    public function test_result_is_cached_per_restaurant_for_five_minutes(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, '2025-01-15 09:00:00', '2026-06-01 10:00:00');

        $first = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats');
        $first->assertJsonPath('total', 1);

        // Insert a row that would change the stats; cached response must not see it.
        $this->insertAuditLog($restaurantId, '2025-02-15 09:00:00', '2026-06-01 10:00:00');

        $cached = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats');
        $cached->assertJsonPath('total', 1);

        Cache::flush();

        $fresh = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats');
        $fresh->assertJsonPath('total', 2);
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
