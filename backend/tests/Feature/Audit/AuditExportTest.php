<?php

namespace Tests\Feature\Audit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_streams_header_and_rows_with_bom(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, '2026-05-01 10:00:00', null, 'caja.opened');
        $this->insertAuditLog($restaurantId, '2026-05-02 10:00:00', null, 'sale.recorded');

        $response = $this
            ->withSession($tenant['session'])
            ->get('/api/admin/audit-log/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
        $response->assertHeader('content-disposition', $response->headers->get('content-disposition'));

        $body = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body, 'CSV must start with UTF-8 BOM for Excel');

        $lines = preg_split("/\r\n/", trim($body));
        $this->assertNotFalse($lines);
        $this->assertSame(3, count($lines), 'Expected 1 header + 2 rows');
        $this->assertStringContainsString('uuid,created_at,archived_at,category,severity,action', $lines[0]);
        $this->assertStringContainsString('caja.opened', $lines[1]);
        $this->assertStringContainsString('sale.recorded', $lines[2]);
    }

    public function test_ndjson_export_streams_one_event_per_line(): void
    {
        $tenant = $this->createTenantSession('admin');
        $this->insertAuditLog($tenant['restaurant_id'], '2026-05-01 10:00:00', null, 'caja.opened');
        $this->insertAuditLog($tenant['restaurant_id'], '2026-05-02 10:00:00', null, 'sale.recorded');

        $response = $this
            ->withSession($tenant['session'])
            ->get('/api/admin/audit-log/export?format=ndjson');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/x-ndjson; charset=utf-8');

        $body = $response->streamedContent();
        $lines = array_filter(explode("\n", $body), static fn ($l) => $l !== '');
        $this->assertSame(2, count($lines));

        $first = json_decode($lines[0], true);
        $this->assertSame('caja.opened', $first['action']);
        $this->assertNull($first['archived_at']);
    }

    public function test_excludes_archived_by_default_and_includes_them_when_requested(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, '2026-05-01 10:00:00', null, 'caja.opened');
        $this->insertAuditLog($restaurantId, '2025-01-01 10:00:00', '2026-06-01 10:00:00', 'caja.opened');

        $default = $this->withSession($tenant['session'])->get('/api/admin/audit-log/export');
        $defaultLines = preg_split("/\r\n/", trim($default->streamedContent()));
        // 1 header + 1 row + 1 meta-event audit.exported emitted before this call returned.
        // Cap to 2 to avoid coupling with the meta-event ordering; we just assert the archived row is not in.
        $this->assertSame(2, count($defaultLines));
        $this->assertStringNotContainsString('2025-01-01', $default->streamedContent());

        $included = $this->withSession($tenant['session'])->get('/api/admin/audit-log/export?include_archived=1');
        $this->assertStringContainsString('2025-01-01', $included->streamedContent());
    }

    public function test_filters_by_category_and_date_range(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, '2026-04-01 10:00:00', null, 'caja.opened', 'caja');
        $this->insertAuditLog($restaurantId, '2026-05-01 10:00:00', null, 'sale.recorded', 'sale');
        $this->insertAuditLog($restaurantId, '2026-05-02 10:00:00', null, 'sale.recorded', 'sale');
        $this->insertAuditLog($restaurantId, '2026-06-01 10:00:00', null, 'caja.closed', 'caja');

        $response = $this
            ->withSession($tenant['session'])
            ->get('/api/admin/audit-log/export?category=sale&date_from=2026-05-01&date_to=2026-05-31');

        $response->assertStatus(200);
        $body = $response->streamedContent();
        $lines = preg_split("/\r\n/", trim($body));
        $this->assertSame(3, count($lines), 'Expected header + 2 sale rows in May');
        $this->assertStringNotContainsString('caja.opened', $body);
        $this->assertStringNotContainsString('caja.closed', $body);
    }

    public function test_records_audit_exported_meta_event_with_row_count_and_filters(): void
    {
        $tenant = $this->createTenantSession('admin');
        $restaurantId = $tenant['restaurant_id'];

        $this->insertAuditLog($restaurantId, '2026-05-01 10:00:00', null, 'caja.opened');
        $this->insertAuditLog($restaurantId, '2026-05-02 10:00:00', null, 'sale.recorded');

        $response = $this
            ->withSession($tenant['session'])
            ->get('/api/admin/audit-log/export?format=csv&include_archived=1');
        $response->streamedContent(); // consume the generator end-to-end

        $exported = DB::table('audit_logs')
            ->where('restaurant_id', $restaurantId)
            ->where('action', 'audit.exported')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($exported, 'audit.exported meta-event must be recorded');
        $meta = json_decode($exported->metadata, true);
        $this->assertSame(2, $meta['row_count']);
        $this->assertSame('csv', $meta['format']);
        $this->assertSame(true, $meta['filters']['include_archived']);
    }

    public function test_non_admin_is_rejected_by_middleware(): void
    {
        $tenant = $this->createTenantSession('operator');

        $response = $this
            ->withSession($tenant['session'])
            ->get('/api/admin/audit-log/export');

        $response->assertStatus(403);
    }

    public function test_rejects_invalid_format(): void
    {
        $tenant = $this->createTenantSession('admin');

        $response = $this
            ->withSession($tenant['session'])
            ->getJson('/api/admin/audit-log/export?format=xml');

        $response->assertStatus(422);
    }

    private function insertAuditLog(
        int $restaurantId,
        string $createdAt,
        ?string $archivedAt,
        string $action = 'test.event',
        string $category = 'system',
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
