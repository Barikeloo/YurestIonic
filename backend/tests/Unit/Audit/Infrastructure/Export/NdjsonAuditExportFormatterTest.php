<?php

namespace Tests\Unit\Audit\Infrastructure\Export;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Infrastructure\Export\NdjsonAuditExportFormatter;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class NdjsonAuditExportFormatterTest extends TestCase
{
    private NdjsonAuditExportFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new NdjsonAuditExportFormatter;
    }

    public function test_header_and_footer_are_empty(): void
    {
        $this->assertSame('', $this->formatter->header());
        $this->assertSame('', $this->formatter->footer());
    }

    public function test_format_row_emits_one_json_object_terminated_by_newline(): void
    {
        $log = AuditLog::fromPersistence(
            uuid: 'a0a1a2a3-a4a5-46a7-88a9-aaabacadaeaf',
            restaurantId: Uuid::generate()->value(),
            entityType: 'order',
            entityId: 'b0b1b2b3-b4b5-46b7-88b9-bbbcbdbebfb0',
            action: 'order.created',
            category: 'order',
            severity: 'info',
            summary: 'Pedido creado',
            reason: null,
            sessionId: null,
            anomalyKind: null,
            integrityHash: str_repeat('0', 64),
            prevHash: null,
            metadata: ['table' => 'T1'],
            userId: null,
            before: null,
            after: ['status' => 'CREATED'],
            ipAddress: '192.168.1.1',
            deviceId: 'tpv-1',
            createdAt: new \DateTimeImmutable('2025-01-15 09:00:00'),
            archivedAt: new \DateTimeImmutable('2026-06-01 02:00:00'),
        );

        $line = $this->formatter->formatRow($log);

        $this->assertStringEndsWith("\n", $line);
        $payload = json_decode(trim($line), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('a0a1a2a3-a4a5-46a7-88a9-aaabacadaeaf', $payload['uuid']);
        $this->assertSame('2025-01-15T09:00:00+00:00', $payload['created_at']);
        $this->assertSame('2026-06-01T02:00:00+00:00', $payload['archived_at']);
        $this->assertSame('order', $payload['category']);
        $this->assertSame('info', $payload['severity']);
        $this->assertSame('order.created', $payload['action']);
        $this->assertSame('order', $payload['entity_type']);
        $this->assertSame('Pedido creado', $payload['summary']);
        $this->assertNull($payload['user_id']);
        $this->assertSame('tpv-1', $payload['device_id']);
        $this->assertSame('192.168.1.1', $payload['ip_address']);
        $this->assertSame(['table' => 'T1'], $payload['metadata']);
        $this->assertNull($payload['before']);
        $this->assertSame(['status' => 'CREATED'], $payload['after']);
    }

    public function test_unicode_is_preserved_unescaped(): void
    {
        $log = AuditLog::fromPersistence(
            uuid: Uuid::generate()->value(),
            restaurantId: Uuid::generate()->value(),
            entityType: 'note',
            entityId: Uuid::generate()->value(),
            action: 'note.recorded',
            category: 'system',
            severity: 'info',
            summary: 'café · 3.20 €',
            reason: 'la razón',
            sessionId: null,
            anomalyKind: null,
            integrityHash: str_repeat('0', 64),
            prevHash: null,
            metadata: ['descripción' => 'línea acentuada'],
            userId: null,
            before: null,
            after: null,
            ipAddress: null,
            deviceId: null,
            createdAt: new \DateTimeImmutable('2025-01-15 09:00:00'),
            archivedAt: null,
        );

        $line = $this->formatter->formatRow($log);
        $this->assertStringContainsString('café · 3.20 €', $line, 'unicode must not be escaped to \\u');
        $this->assertStringContainsString('descripción', $line);

        $payload = json_decode(trim($line), true);
        $this->assertSame('café · 3.20 €', $payload['summary']);
        $this->assertSame('la razón', $payload['reason']);
        $this->assertSame(['descripción' => 'línea acentuada'], $payload['metadata']);
    }
}
