<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditVerifyLatestTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_when_no_verification_has_been_run(): void
    {
        $tenant = $this->createTenantSession('admin');

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/verify/latest');

        $response->assertStatus(200);
        $response->assertJsonPath('latest', null);
    }

    public function test_returns_latest_verification_result(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        DB::table('audit_chain_verifications')->insert([
            'restaurant_id' => $restaurantId,
            'is_valid' => true,
            'total_events' => 10,
            'verified_count' => 10,
            'broken_events' => json_encode([]),
            'first_broken_index' => null,
            'verified_at' => '2026-06-05 10:00:00',
            'created_at' => '2026-06-05 10:00:00',
            'updated_at' => '2026-06-05 10:00:00',
        ]);

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/verify/latest');

        $response->assertStatus(200);
        $response->assertJsonPath('latest.is_valid', true);
        $response->assertJsonPath('latest.total_events', 10);
        $response->assertJsonPath('latest.verified_count', 10);
        $response->assertJsonPath('latest.broken_events', []);
        $response->assertJsonPath('latest.first_broken_index', null);
        $response->assertJsonPath('latest.verified_at', '2026-06-05T10:00:00+00:00');
    }

    public function test_returns_latest_verification_with_broken_events(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        DB::table('audit_chain_verifications')->insert([
            'restaurant_id' => $restaurantId,
            'is_valid' => false,
            'total_events' => 5,
            'verified_count' => 4,
            'broken_events' => json_encode([
                ['uuid' => 'some-uuid', 'expected_hash' => 'abc', 'actual_hash' => 'def'],
            ]),
            'first_broken_index' => 2,
            'verified_at' => '2026-06-05 11:00:00',
            'created_at' => '2026-06-05 11:00:00',
            'updated_at' => '2026-06-05 11:00:00',
        ]);

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/verify/latest');

        $response->assertStatus(200);
        $response->assertJsonPath('latest.is_valid', false);
        $response->assertJsonPath('latest.total_events', 5);
        $response->assertJsonPath('latest.verified_count', 4);
        $response->assertJsonPath('latest.first_broken_index', 2);
        $response->assertJsonCount(1, 'latest.broken_events');
    }

    public function test_returns_only_the_latest_verification(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        DB::table('audit_chain_verifications')->insert([
            'restaurant_id' => $restaurantId,
            'is_valid' => false,
            'total_events' => 3,
            'verified_count' => 2,
            'broken_events' => json_encode([]),
            'first_broken_index' => null,
            'verified_at' => '2026-06-05 09:00:00',
            'created_at' => '2026-06-05 09:00:00',
            'updated_at' => '2026-06-05 09:00:00',
        ]);

        DB::table('audit_chain_verifications')->insert([
            'restaurant_id' => $restaurantId,
            'is_valid' => true,
            'total_events' => 10,
            'verified_count' => 10,
            'broken_events' => json_encode([]),
            'first_broken_index' => null,
            'verified_at' => '2026-06-05 10:00:00',
            'created_at' => '2026-06-05 10:00:00',
            'updated_at' => '2026-06-05 10:00:00',
        ]);

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/verify/latest');

        $response->assertStatus(200);
        $response->assertJsonPath('latest.is_valid', true);
        $response->assertJsonPath('latest.total_events', 10);
    }

    public function test_scope_is_per_restaurant(): void
    {
        $tenantA = $this->createTenantSession('admin');
        $tenantB = $this->createTenantSession('admin');

        DB::table('audit_chain_verifications')->insert([
            'restaurant_id' => $tenantA['restaurant_id'],
            'is_valid' => true,
            'total_events' => 7,
            'verified_count' => 7,
            'broken_events' => json_encode([]),
            'first_broken_index' => null,
            'verified_at' => '2026-06-05 10:00:00',
            'created_at' => '2026-06-05 10:00:00',
            'updated_at' => '2026-06-05 10:00:00',
        ]);

        $responseB = $this
            ->withSession($tenantB['session'])
            ->getJson('/api/admin/audit-log/verify/latest');

        $responseB->assertStatus(200);
        $responseB->assertJsonPath('latest', null);
    }

    public function test_non_admin_is_rejected_by_middleware(): void
    {
        $tenant = $this->createTenantSession('operator');

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/verify/latest');

        $response->assertStatus(403);
    }
}
