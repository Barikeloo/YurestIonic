<?php

namespace Tests\Feature\Audit;

use App\Audit\Application\ArchiveAuditData\ArchiveOldAuditLogs;
use App\Audit\Application\ArchiveAuditData\ArchiveOldAuditLogsCommand;
use App\Audit\Domain\AuditChainHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditRetentionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private AuditChainHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hasher = new AuditChainHasher;
    }

    public function test_archive_then_panel_then_export_then_verify_all_agree(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];
        $restaurantUuid = $tenant['restaurant_uuid'];

        $prevUuid = null;
        $expectedArchived = 0;
        for ($monthsAgo = 12; $monthsAgo >= 4; $monthsAgo--) {
            for ($i = 0; $i < 5; $i++) {
                $createdAt = (new \DateTimeImmutable("-{$monthsAgo} months -{$i} days"))->format('Y-m-d H:i:s');
                $prevUuid = $this->insertChainedEvent($restaurantId, $restaurantUuid, $prevUuid, $createdAt, 'caja.opened');
                $expectedArchived++;
            }
        }
        for ($i = 0; $i < 5; $i++) {
            $createdAt = (new \DateTimeImmutable("-{$i} days"))->format('Y-m-d H:i:s');
            $prevUuid = $this->insertChainedEvent($restaurantId, $restaurantUuid, $prevUuid, $createdAt, 'sale.recorded');
        }

        $archive = $this->app->make(ArchiveOldAuditLogs::class);
        $archiveResponse = ($archive)(new ArchiveOldAuditLogsCommand(
            olderThanDays: 90,
            restaurantUuid: $restaurantUuid,
            dryRun: false,
        ));
        $this->assertSame($expectedArchived, $archiveResponse->totalArchived);

        $this->assertSame(
            $expectedArchived,
            DB::table('audit_logs')->where('restaurant_id', $restaurantId)->whereNotNull('archived_at')->count(),
        );
        $this->assertSame(
            5,
            DB::table('audit_logs')
                ->where('restaurant_id', $restaurantId)
                ->whereNull('archived_at')
                ->where('action', 'sale.recorded')
                ->count(),
            'recent rows must stay live',
        );

        $stats = $this->withSession($tenant['session'])->getJson('/api/admin/audit-log/archived-stats');
        $stats->assertStatus(200);
        $stats->assertJsonPath('total', $expectedArchived);
        $statsBody = $stats->json();
        $this->assertNotNull($statsBody['oldest_created_at']);
        $this->assertNotNull($statsBody['newest_created_at']);
        $this->assertGreaterThan(0, count($statsBody['monthly_breakdown']));

        $export = $this->withSession($tenant['session'])
            ->get('/api/admin/audit-log/export?include_archived=1&format=csv');
        $export->assertStatus(200);
        $body = $export->streamedContent();
        $lines = preg_split("/\r\n/", trim($body));

        $this->assertGreaterThanOrEqual($expectedArchived + 1, count($lines));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('caja.opened', $body);

        $verify = $this->withSession($tenant['session'])->getJson('/api/admin/audit-log/verify');
        $verify->assertStatus(200);
        $verify->assertJsonPath('is_valid', true);
        $verify->assertJsonPath('broken_events', []);

        $this->assertSame(
            1,
            DB::table('audit_logs')->where('restaurant_id', $restaurantId)->where('action', 'audit.archived')->count(),
            'one audit.archived per restaurant per run',
        );
        $exported = DB::table('audit_logs')->where('restaurant_id', $restaurantId)->where('action', 'audit.exported')->first();
        $this->assertNotNull($exported, 'audit.exported must be recorded for the CSV download');
        $exportedMeta = json_decode($exported->metadata, true);
        $this->assertSame('csv', $exportedMeta['format']);
        $this->assertSame(true, $exportedMeta['filters']['include_archived']);
    }

    public function test_panel_filtered_by_date_range_only_counts_matching_rows(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        $this->insertEvent($restaurantId, '2025-01-15 10:00:00', archivedAt: '2026-06-01 02:00:00');
        $this->insertEvent($restaurantId, '2025-01-20 10:00:00', archivedAt: '2026-06-01 02:00:00');
        $this->insertEvent($restaurantId, '2025-04-05 10:00:00', archivedAt: '2026-06-01 02:00:00');
        $this->insertEvent($restaurantId, '2025-08-12 10:00:00', archivedAt: '2026-06-01 02:00:00');

        $january = $this->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats?date_from=2025-01-01&date_to=2025-01-31');
        $january->assertJsonPath('total', 2);

        $h1 = $this->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats?date_from=2025-01-01&date_to=2025-06-30');
        $h1->assertJsonPath('total', 3);

        $allTime = $this->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/archived-stats');
        $allTime->assertJsonPath('total', 4);
    }

    public function test_archive_run_invalidates_the_unfiltered_stats_cache(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];
        $restaurantUuid = $tenant['restaurant_uuid'];

        $this->insertEvent($restaurantId, (new \DateTimeImmutable('-200 days'))->format('Y-m-d H:i:s'));
        $this->insertEvent($restaurantId, (new \DateTimeImmutable('-150 days'))->format('Y-m-d H:i:s'));

        $first = $this->withSession($tenant['session'])->getJson('/api/admin/audit-log/archived-stats');
        $first->assertJsonPath('total', 0);

        $archive = $this->app->make(ArchiveOldAuditLogs::class);
        ($archive)(new ArchiveOldAuditLogsCommand(olderThanDays: 90, restaurantUuid: $restaurantUuid, dryRun: false));

        $second = $this->withSession($tenant['session'])->getJson('/api/admin/audit-log/archived-stats');
        $second->assertJsonPath('total', 2);
        $this->assertSame(2, $second->json('total'), 'cache must invalidate so the panel sees the new archive');
    }

    private function insertChainedEvent(
        int $restaurantId,
        string $restaurantUuid,
        ?string $prevUuid,
        string $createdAt,
        string $action,
    ): string {
        $uuid = (string) Str::uuid();
        $entityId = (string) Str::uuid();

        $prevHash = $prevUuid !== null
            ? DB::table('audit_logs')->where('uuid', $prevUuid)->value('integrity_hash')
            : null;

        $hash = $this->hasher->compute(
            prevHash: $prevHash,
            uuid: $uuid,
            restaurantUuid: $restaurantUuid,
            createdAtIso: $createdAt,
            actionSlug: $action,
            entityType: 'test',
            entityId: $entityId,
            userUuid: null,
            summary: 'Lifecycle event',
            metadata: [],
            before: null,
            after: null,
        );

        DB::table('audit_logs')->insert([
            'uuid' => $uuid,
            'restaurant_id' => $restaurantId,
            'entity_type' => 'test',
            'entity_id' => $entityId,
            'action' => $action,
            'category' => str_starts_with($action, 'caja') ? 'caja' : 'sale',
            'severity' => 'info',
            'summary' => 'Lifecycle event',
            'reason' => null,
            'session_id' => null,
            'anomaly_kind' => null,
            'integrity_hash' => $hash,
            'prev_hash' => $prevHash,
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

    private function insertEvent(
        int $restaurantId,
        string $createdAt,
        ?string $archivedAt = null,
        string $action = 'caja.opened',
        string $category = 'caja',
    ): string {
        $uuid = (string) Str::uuid();

        DB::table('audit_logs')->insert([
            'uuid' => $uuid,
            'restaurant_id' => $restaurantId,
            'entity_type' => 'test',
            'entity_id' => (string) Str::uuid(),
            'action' => $action,
            'category' => $category,
            'severity' => 'info',
            'summary' => 'Test',
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
