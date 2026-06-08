<?php

namespace Tests\Feature\Audit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditArchiveOldCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_old_rows_as_archived_and_skips_recent_ones(): void
    {
        $tenant = $this->createTenantSession();
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, now()->subDays(120));
        $this->insertAuditLog($restaurantId, now()->subDays(100));
        $recentUuid = $this->insertAuditLog($restaurantId, now()->subDay());

        $this->artisan('audit:archive-old', ['--older-than-days' => 90])
            ->assertExitCode(0);

        $this->assertSame(2, DB::table('audit_logs')
            ->where('restaurant_id', $restaurantId)
            ->whereNotNull('archived_at')
            ->count());

        $this->assertNull(DB::table('audit_logs')->where('uuid', $recentUuid)->value('archived_at'));
    }

    public function test_dry_run_does_not_modify_rows(): void
    {
        $tenant = $this->createTenantSession();
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, now()->subDays(200));
        $this->insertAuditLog($restaurantId, now()->subDays(180));

        $this->artisan('audit:archive-old', ['--older-than-days' => 90, '--dry-run' => true])
            ->expectsOutputToContain('[DRY-RUN] archived 2 audit log(s)')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('audit_logs')
            ->where('restaurant_id', $restaurantId)
            ->whereNotNull('archived_at')
            ->count());
    }

    public function test_restaurant_uuid_option_restricts_the_scope(): void
    {
        $tenantA = $this->createTenantSession();
        $tenantB = $this->createTenantSession();

        $this->insertAuditLog($tenantA['restaurant_id'], now()->subDays(120));
        $this->insertAuditLog($tenantB['restaurant_id'], now()->subDays(120));

        $this->artisan('audit:archive-old', [
            '--older-than-days' => 90,
            '--restaurant-uuid' => $tenantA['restaurant_uuid'],
        ])->assertExitCode(0);

        $this->assertSame(1, DB::table('audit_logs')
            ->where('restaurant_id', $tenantA['restaurant_id'])
            ->whereNotNull('archived_at')
            ->count());

        $this->assertSame(0, DB::table('audit_logs')
            ->where('restaurant_id', $tenantB['restaurant_id'])
            ->whereNotNull('archived_at')
            ->count());
    }

    public function test_rejects_zero_or_negative_threshold(): void
    {
        $this->artisan('audit:archive-old', ['--older-than-days' => 0])
            ->assertExitCode(2);

    }

    public function test_re_running_is_idempotent(): void
    {
        $tenant = $this->createTenantSession();
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, now()->subDays(120));

        $this->artisan('audit:archive-old', ['--older-than-days' => 90])->assertExitCode(0);
        $firstArchivedAt = DB::table('audit_logs')->where('restaurant_id', $restaurantId)->value('archived_at');

        sleep(1);

        $this->artisan('audit:archive-old', ['--older-than-days' => 90])
            ->expectsOutputToContain('archived 0 audit log(s)')
            ->assertExitCode(0);

        $this->assertSame(
            $firstArchivedAt,
            DB::table('audit_logs')->where('restaurant_id', $restaurantId)->value('archived_at'),
        );
    }

    private function insertAuditLog(int $restaurantId, $createdAt): string
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
            'archived_at' => null,
        ]);

        return $uuid;
    }
}
