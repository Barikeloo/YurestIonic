<?php

namespace Tests\Feature\Audit;

use App\Audit\Domain\AuditChainHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditVerifyChainTest extends TestCase
{
    use RefreshDatabase;

    private AuditChainHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new AuditChainHasher;
    }

    public function test_chain_is_valid_when_archived_events_have_correct_hashes(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];
        $restaurantUuid = $tenant['restaurant_uuid'];

        $uuid1 = $this->insertLog($restaurantId, $restaurantUuid, null, 'Event 1', '2026-05-01 10:00:00', false);
        $uuid2 = $this->insertLog($restaurantId, $restaurantUuid, $uuid1, 'Event 2', '2026-05-02 10:00:00', true);
        $this->insertLog($restaurantId, $restaurantUuid, $uuid2, 'Event 3', '2026-05-03 10:00:00', false);

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/verify');

        $response->assertStatus(200);
        $response->assertJson([
            'total_events' => 3,
            'verified_count' => 3,
            'broken_events' => [],
            'first_broken_index' => null,
            'is_valid' => true,
        ]);
    }

    public function test_chain_detects_corrupted_archived_event(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];
        $restaurantUuid = $tenant['restaurant_uuid'];

        $uuid1 = $this->insertLog($restaurantId, $restaurantUuid, null, 'Event 1', '2026-05-01 10:00:00', false);

        $uuid2 = (string) Str::uuid();
        $entityId2 = (string) Str::uuid();
        $event1Hash = DB::table('audit_logs')->where('uuid', $uuid1)->value('integrity_hash');
        $corruptedHash = str_repeat('a', 64);

        DB::table('audit_logs')->insert([
            'uuid' => $uuid2,
            'restaurant_id' => $restaurantId,
            'entity_type' => 'order',
            'entity_id' => $entityId2,
            'action' => 'test.event',
            'category' => 'system',
            'severity' => 'info',
            'summary' => 'Event 2',
            'reason' => null,
            'session_id' => null,
            'anomaly_kind' => null,
            'integrity_hash' => $corruptedHash,
            'prev_hash' => $event1Hash,
            'metadata' => json_encode([]),
            'user_id' => null,
            'before' => null,
            'after' => null,
            'ip_address' => null,
            'device_id' => null,
            'created_at' => '2026-05-02 10:00:00',
            'archived_at' => '2026-06-01 10:00:00',
        ]);

        $uuid3 = (string) Str::uuid();
        $entityId3 = (string) Str::uuid();
        $hash3 = $this->hasher->compute(
            prevHash: $corruptedHash,
            uuid: $uuid3,
            restaurantUuid: $restaurantUuid,
            createdAtIso: '2026-05-03 10:00:00',
            actionSlug: 'test.event',
            entityType: 'order',
            entityId: $entityId3,
            userUuid: null,
            summary: 'Event 3',
            metadata: [],
            before: null,
            after: null,
        );
        DB::table('audit_logs')->insert([
            'uuid' => $uuid3,
            'restaurant_id' => $restaurantId,
            'entity_type' => 'order',
            'entity_id' => $entityId3,
            'action' => 'test.event',
            'category' => 'system',
            'severity' => 'info',
            'summary' => 'Event 3',
            'reason' => null,
            'session_id' => null,
            'anomaly_kind' => null,
            'integrity_hash' => $hash3,
            'prev_hash' => $corruptedHash,
            'metadata' => json_encode([]),
            'user_id' => null,
            'before' => null,
            'after' => null,
            'ip_address' => null,
            'device_id' => null,
            'created_at' => '2026-05-03 10:00:00',
            'archived_at' => null,
        ]);

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/verify');

        $response->assertStatus(200);
        $response->assertJsonPath('is_valid', false);
        $response->assertJsonCount(1, 'broken_events');
        $response->assertJsonPath('first_broken_index', 1);
        $response->assertJsonPath('broken_events.0.uuid', $uuid2);
    }

    private function insertLog(
        int $restaurantId,
        string $restaurantUuid,
        ?string $prevUuid,
        string $summary,
        string $createdAt,
        bool $archived,
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
            actionSlug: 'test.event',
            entityType: 'order',
            entityId: $entityId,
            userUuid: null,
            summary: $summary,
            metadata: [],
            before: null,
            after: null,
        );

        DB::table('audit_logs')->insert([
            'uuid' => $uuid,
            'restaurant_id' => $restaurantId,
            'entity_type' => 'order',
            'entity_id' => $entityId,
            'action' => 'test.event',
            'category' => 'system',
            'severity' => 'info',
            'summary' => $summary,
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
            'archived_at' => $archived ? '2026-06-01 10:00:00' : null,
        ]);

        return $uuid;
    }
}
