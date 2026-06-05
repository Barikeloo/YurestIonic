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

    public function test_returns_by_category_breakdown_grouped_and_sorted_by_count_desc(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];
        $archivedAt = '2026-06-01 10:00:00';

        // 3 caja + 2 sale + 1 order, plus a non-archived row that must be ignored.
        $this->insertAuditLog($restaurantId, '2025-01-15 09:00:00', $archivedAt, 'caja');
        $this->insertAuditLog($restaurantId, '2025-01-16 09:00:00', $archivedAt, 'caja');
        $this->insertAuditLog($restaurantId, '2025-01-17 09:00:00', $archivedAt, 'caja');
        $this->insertAuditLog($restaurantId, '2025-02-10 09:00:00', $archivedAt, 'sale');
        $this->insertAuditLog($restaurantId, '2025-02-11 09:00:00', $archivedAt, 'sale');
        $this->insertAuditLog($restaurantId, '2025-03-05 09:00:00', $archivedAt, 'order');
        $this->insertAuditLog($restaurantId, '2026-05-01 09:00:00', null, 'caja');

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats');

        $response->assertStatus(200);
        $response->assertJsonPath('by_category', [
            ['category' => 'caja',  'count' => 3],
            ['category' => 'sale',  'count' => 2],
            ['category' => 'order', 'count' => 1],
        ]);
    }

    public function test_returns_top_users_with_uuid_name_role_and_count_descending(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];
        $archivedAt = '2026-06-01 10:00:00';

        $manolo = $this->insertRestaurantUser($restaurantId, 'Manolo', 'admin');
        $maria  = $this->insertRestaurantUser($restaurantId, 'María',  'supervisor');
        $carlos = $this->insertRestaurantUser($restaurantId, 'Carlos', 'operator');

        // Manolo: 4, María: 2, Carlos: 1
        foreach (range(1, 4) as $d) $this->insertAuditLog($restaurantId, "2025-01-0{$d} 09:00:00", $archivedAt, 'caja', $manolo['id']);
        foreach (range(5, 6) as $d) $this->insertAuditLog($restaurantId, "2025-01-0{$d} 09:00:00", $archivedAt, 'sale', $maria['id']);
        $this->insertAuditLog($restaurantId, '2025-01-07 09:00:00', $archivedAt, 'order', $carlos['id']);

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats');

        $response->assertStatus(200);
        $response->assertJsonPath('top_users', [
            ['uuid' => $manolo['uuid'], 'name' => 'Manolo', 'role' => 'admin',      'count' => 4],
            ['uuid' => $maria['uuid'],  'name' => 'María',  'role' => 'supervisor', 'count' => 2],
            ['uuid' => $carlos['uuid'], 'name' => 'Carlos', 'role' => 'operator',   'count' => 1],
        ]);
    }

    public function test_top_users_excludes_archived_events_with_null_user_id(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];
        $archivedAt = '2026-06-01 10:00:00';

        $manolo = $this->insertRestaurantUser($restaurantId, 'Manolo', 'admin');

        // 2 system-attributed (user_id = null) + 1 attributed to Manolo.
        $this->insertAuditLog($restaurantId, '2025-01-01 09:00:00', $archivedAt, 'system', null);
        $this->insertAuditLog($restaurantId, '2025-01-02 09:00:00', $archivedAt, 'system', null);
        $this->insertAuditLog($restaurantId, '2025-01-03 09:00:00', $archivedAt, 'caja', $manolo['id']);

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats');

        $response->assertStatus(200);
        $response->assertJsonPath('top_users', [
            ['uuid' => $manolo['uuid'], 'name' => 'Manolo', 'role' => 'admin', 'count' => 1],
        ]);
    }

    public function test_top_users_is_capped_at_five(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];
        $archivedAt = '2026-06-01 10:00:00';

        // 6 users, each with a different count so the ordering is unambiguous.
        $users = [];
        $base = new \DateTimeImmutable('2025-01-01 09:00:00');
        $offset = 0;
        foreach (range(1, 6) as $i) {
            $users[$i] = $this->insertRestaurantUser($restaurantId, "User{$i}", 'operator');
            for ($n = 0; $n < (7 - $i); $n++) {
                $createdAt = $base->modify("+{$offset} hours")->format('Y-m-d H:i:s');
                $this->insertAuditLog($restaurantId, $createdAt, $archivedAt, 'caja', $users[$i]['id']);
                $offset++;
            }
        }

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'top_users');
        $response->assertJsonPath('top_users.0.uuid', $users[1]['uuid']);
        $response->assertJsonPath('top_users.0.count', 6);
        $response->assertJsonPath('top_users.4.uuid', $users[5]['uuid']);
        $response->assertJsonPath('top_users.4.count', 2);
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

    private function insertAuditLog(
        int $restaurantId,
        string $createdAt,
        ?string $archivedAt,
        string $category = 'system',
        ?int $userId = null,
    ): string {
        $uuid = (string) Str::uuid();

        DB::table('audit_logs')->insert([
            'uuid' => $uuid,
            'restaurant_id' => $restaurantId,
            'entity_type' => 'test',
            'entity_id' => (string) Str::uuid(),
            'action' => 'test.event',
            'category' => $category,
            'severity' => 'info',
            'summary' => 'Test event',
            'reason' => null,
            'session_id' => null,
            'anomaly_kind' => null,
            'integrity_hash' => str_repeat('0', 64),
            'prev_hash' => null,
            'metadata' => json_encode([]),
            'user_id' => $userId,
            'before' => null,
            'after' => null,
            'ip_address' => null,
            'device_id' => null,
            'created_at' => $createdAt,
            'archived_at' => $archivedAt,
        ]);

        return $uuid;
    }

    /**
     * @return array{id: int, uuid: string, name: string, role: string}
     */
    private function insertRestaurantUser(int $restaurantId, string $name, string $role): array
    {
        $uuid = (string) Str::uuid();
        $id = (int) DB::table('users')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => $uuid,
            'role' => $role,
            'name' => $name,
            'email' => 'u-'.Str::lower(Str::random(10)).'@local.test',
            'password' => str_repeat('x', 60),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 'uuid' => $uuid, 'name' => $name, 'role' => $role];
    }
}
