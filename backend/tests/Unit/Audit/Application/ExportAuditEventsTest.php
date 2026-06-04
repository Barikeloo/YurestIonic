<?php

namespace Tests\Unit\Audit\Application;

use App\Audit\Application\ExportAuditEvents\ExportAuditEvents;
use App\Audit\Application\ExportAuditEvents\ExportAuditEventsCommand;
use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ListAuditLogsCriteria;
use App\Audit\Domain\ValueObject\ExportFormat;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ExportAuditEventsTest extends TestCase
{
    private AuditLogRepositoryInterface&MockInterface $repository;
    private AuditRecorderInterface&MockInterface $auditRecorder;
    private ExportAuditEvents $useCase;
    private string $restaurantUuid;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AuditLogRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);
        $this->useCase = new ExportAuditEvents($this->repository, $this->auditRecorder);
        $this->restaurantUuid = Uuid::generate()->value();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_yields_each_log_in_order_and_emits_exported_event(): void
    {
        $logs = [
            $this->makeLog('caja.opened', '2025-01-01 10:00:00'),
            $this->makeLog('sale.recorded', '2025-01-02 10:00:00'),
            $this->makeLog('caja.closed', '2025-01-03 10:00:00'),
        ];

        $this->repository
            ->shouldReceive('streamForExport')
            ->once()
            ->andReturnUsing(static function () use ($logs): \Generator {
                foreach ($logs as $log) {
                    yield $log;
                }
            });

        $captured = null;
        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::on(static function (AuditEventDraft $draft) use (&$captured): bool {
                $captured = $draft;
                return $draft->slug->value() === 'audit.exported';
            }));

        $yielded = iterator_to_array(
            ($this->useCase)(new ExportAuditEventsCommand($this->restaurantUuid, ExportFormat::Csv)),
            preserve_keys: false,
        );

        $this->assertSame($logs, $yielded, 'use case must yield exactly the logs from the repo, in order');
        $this->assertNotNull($captured);
        $this->assertSame(3, $captured->metadata['row_count']);
        $this->assertSame('csv', $captured->metadata['format']);
        $this->assertSame($this->restaurantUuid, $captured->entityId);
    }

    public function test_does_not_emit_event_when_generator_is_not_consumed(): void
    {
        $this->repository
            ->shouldReceive('streamForExport')
            ->never();

        $this->auditRecorder
            ->shouldReceive('record')
            ->never();

        // Invoke but don't iterate: the use case is a generator function, so
        // its body only runs once consumed. An aborted client export must not
        // leave an audit.exported entry pointing at a half-written download.
        ($this->useCase)(new ExportAuditEventsCommand($this->restaurantUuid, ExportFormat::Csv));

        $this->assertTrue(true, 'no expectations should fire when the generator is never iterated');
    }

    public function test_forwards_command_filters_into_repository_criteria(): void
    {
        $captured = null;
        $this->repository
            ->shouldReceive('streamForExport')
            ->once()
            ->with(Mockery::on(static function (ListAuditLogsCriteria $c) use (&$captured): bool {
                $captured = $c;
                return true;
            }))
            ->andReturn(new \ArrayIterator([]));

        $this->auditRecorder->shouldReceive('record')->once();

        iterator_to_array(($this->useCase)(new ExportAuditEventsCommand(
            restaurantId: $this->restaurantUuid,
            format: ExportFormat::Ndjson,
            category: 'caja',
            severity: 'warning',
            userId: ($u = Uuid::generate()->value()),
            deviceId: 'dev-1',
            dateFrom: '2025-01-15',
            dateTo: '2025-03-31',
            search: 'apertura',
            anomalyOnly: true,
            includeArchived: true,
        )));

        $this->assertNotNull($captured);
        $this->assertSame($this->restaurantUuid, $captured->restaurantId->value());
        $this->assertSame('caja', $captured->category);
        $this->assertSame('warning', $captured->severity);
        $this->assertSame($u, $captured->userId?->value());
        $this->assertSame('dev-1', $captured->deviceId);
        $this->assertSame('2025-01-15 00:00:00', $captured->dateFrom?->format('Y-m-d H:i:s'));
        $this->assertSame('2025-03-31 23:59:59', $captured->dateTo?->format('Y-m-d H:i:s'));
        $this->assertSame('apertura', $captured->search);
        $this->assertTrue($captured->anomalyOnly);
        $this->assertTrue($captured->includeArchived);
    }

    public function test_filters_metadata_drops_null_and_false_only_for_booleans(): void
    {
        $this->repository
            ->shouldReceive('streamForExport')
            ->once()
            ->andReturn(new \ArrayIterator([]));

        $captured = null;
        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::on(static function (AuditEventDraft $draft) use (&$captured): bool {
                $captured = $draft;
                return true;
            }));

        iterator_to_array(($this->useCase)(new ExportAuditEventsCommand(
            restaurantId: $this->restaurantUuid,
            format: ExportFormat::Csv,
            category: 'sale',
            anomalyOnly: false,
            includeArchived: false,
        )));

        $this->assertNotNull($captured);
        $filters = $captured->metadata['filters'];
        $this->assertSame('sale', $filters['category']);
        $this->assertArrayNotHasKey('severity', $filters);
        $this->assertArrayNotHasKey('user_id', $filters);
        $this->assertArrayNotHasKey('anomaly_only', $filters, 'false flags should not appear in the meta-event');
        $this->assertArrayNotHasKey('include_archived', $filters);
    }

    private function makeLog(string $slug, string $createdAt): AuditLog
    {
        return AuditLog::fromPersistence(
            uuid: Uuid::generate()->value(),
            restaurantId: $this->restaurantUuid,
            entityType: 'test',
            entityId: Uuid::generate()->value(),
            action: $slug,
            category: 'system',
            severity: 'info',
            summary: 'Test',
            reason: null,
            sessionId: null,
            anomalyKind: null,
            integrityHash: str_repeat('0', 64),
            prevHash: null,
            metadata: [],
            userId: null,
            before: null,
            after: null,
            ipAddress: null,
            deviceId: null,
            createdAt: new \DateTimeImmutable($createdAt),
            archivedAt: null,
        );
    }
}
