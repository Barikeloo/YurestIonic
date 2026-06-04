<?php

namespace Tests\Unit\Audit\Infrastructure\Export;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Infrastructure\Export\CsvAuditExportFormatter;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class CsvAuditExportFormatterTest extends TestCase
{
    private CsvAuditExportFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new CsvAuditExportFormatter;
    }

    public function test_header_starts_with_utf8_bom_and_lists_all_columns(): void
    {
        $header = $this->formatter->header();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $header, 'CSV must start with the UTF-8 BOM so Excel renders accents');
        $this->assertStringEndsWith("\r\n", $header, 'Excel expects CRLF line endings');

        $columns = explode(',', substr(trim($header), 3));
        $this->assertSame([
            'uuid', 'created_at', 'archived_at', 'category', 'severity', 'action',
            'entity_type', 'entity_id', 'summary', 'reason', 'user_id', 'device_id',
            'ip_address', 'session_id', 'anomaly_kind', 'integrity_hash', 'prev_hash',
            'metadata', 'before', 'after',
        ], $columns);
    }

    public function test_format_row_serialises_a_live_event(): void
    {
        $log = $this->makeLog(
            uuid: 'a0a1a2a3-a4a5-46a7-88a9-aaabacadaeaf',
            entityType: 'order',
            action: 'order.created',
            summary: 'Pedido creado',
            metadata: ['table' => 'T1', 'amount_cents' => 320],
            archivedAt: null,
        );

        $line = $this->formatter->formatRow($log);

        $this->assertStringEndsWith("\r\n", $line);
        $cells = str_getcsv(trim($line));
        $this->assertSame('a0a1a2a3-a4a5-46a7-88a9-aaabacadaeaf', $cells[0]);
        $this->assertSame('2025-01-15T09:00:00+00:00', $cells[1]);
        $this->assertSame('', $cells[2], 'archived_at must be empty for live events');
        $this->assertSame('system', $cells[3]);
        $this->assertSame('info', $cells[4]);
        $this->assertSame('order.created', $cells[5]);
        $this->assertSame('order', $cells[6]);
        $this->assertSame('Pedido creado', $cells[8]);

        $metadataCell = $cells[17];
        $this->assertSame(['table' => 'T1', 'amount_cents' => 320], json_decode($metadataCell, true));
    }

    public function test_archived_at_is_emitted_when_present(): void
    {
        $log = $this->makeLog(
            uuid: Uuid::generate()->value(),
            entityType: 'order',
            action: 'order.created',
            summary: 'archived row',
            metadata: [],
            archivedAt: new \DateTimeImmutable('2026-06-01 02:00:00'),
        );

        $cells = str_getcsv(trim($this->formatter->formatRow($log)));
        $this->assertSame('2026-06-01T02:00:00+00:00', $cells[2]);
    }

    public function test_quotes_and_unicode_round_trip_cleanly(): void
    {
        $log = $this->makeLog(
            uuid: Uuid::generate()->value(),
            entityType: 'note',
            action: 'note.recorded',
            summary: 'Razón: "café con leche", €3.20',
            metadata: ['nota' => 'línea con "comillas" y, comas'],
            archivedAt: null,
        );

        $cells = str_getcsv(trim($this->formatter->formatRow($log)));

        $this->assertSame('Razón: "café con leche", €3.20', $cells[8]);
        $this->assertSame(
            ['nota' => 'línea con "comillas" y, comas'],
            json_decode($cells[17], true),
        );
    }

    public function test_empty_metadata_renders_as_empty_cell(): void
    {
        $log = $this->makeLog(
            uuid: Uuid::generate()->value(),
            entityType: 'x',
            action: 'system.boot',
            summary: 'boot',
            metadata: [],
            archivedAt: null,
        );

        $cells = str_getcsv(trim($this->formatter->formatRow($log)));
        $this->assertSame('', $cells[17]);
        $this->assertSame('', $cells[18]);
        $this->assertSame('', $cells[19]);
    }

    public function test_footer_is_empty(): void
    {
        $this->assertSame('', $this->formatter->footer());
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function makeLog(
        string $uuid,
        string $entityType,
        string $action,
        string $summary,
        array $metadata,
        ?\DateTimeImmutable $archivedAt,
    ): AuditLog {
        return AuditLog::fromPersistence(
            uuid: $uuid,
            restaurantId: Uuid::generate()->value(),
            entityType: $entityType,
            entityId: Uuid::generate()->value(),
            action: $action,
            category: 'system',
            severity: 'info',
            summary: $summary,
            reason: null,
            sessionId: null,
            anomalyKind: null,
            integrityHash: str_repeat('0', 64),
            prevHash: null,
            metadata: $metadata,
            userId: null,
            before: null,
            after: null,
            ipAddress: null,
            deviceId: null,
            createdAt: new \DateTimeImmutable('2025-01-15 09:00:00'),
            archivedAt: $archivedAt,
        );
    }
}
