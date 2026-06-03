<?php

namespace Tests\Unit\Audit\Application;

use App\Audit\Application\ArchiveAuditData\ArchiveOldAuditLogs;
use App\Audit\Application\ArchiveAuditData\ArchiveOldAuditLogsCommand;
use App\Audit\Domain\ArchiveStats;
use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Exception\InvalidArchiveThresholdException;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ArchiveOldAuditLogsTest extends TestCase
{
    private AuditLogRepositoryInterface&MockInterface $repository;
    private AuditRecorderInterface&MockInterface $auditRecorder;
    private ArchiveOldAuditLogs $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AuditLogRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);
        $this->useCase = new ArchiveOldAuditLogs($this->repository, $this->auditRecorder);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_archives_across_all_restaurants_when_no_uuid_is_given(): void
    {
        $stats = [
            new ArchiveStats(
                restaurantId: Uuid::generate(),
                archivedCount: 4,
                oldestCreatedAt: new \DateTimeImmutable('2025-01-01 09:00:00'),
                newestCreatedAt: new \DateTimeImmutable('2025-03-01 09:00:00'),
            ),
            new ArchiveStats(
                restaurantId: Uuid::generate(),
                archivedCount: 7,
                oldestCreatedAt: new \DateTimeImmutable('2025-02-01 12:00:00'),
                newestCreatedAt: new \DateTimeImmutable('2025-02-28 12:00:00'),
            ),
        ];

        $this->repository
            ->shouldReceive('bulkArchive')
            ->once()
            ->with(null, Mockery::type(\DateTimeImmutable::class), false)
            ->andReturn($stats);

        $this->auditRecorder
            ->shouldReceive('record')
            ->times(2)
            ->with(Mockery::on(static function (AuditEventDraft $draft): bool {
                return $draft->slug->value() === 'audit.archived'
                    && $draft->entityType === 'audit_log'
                    && isset($draft->metadata['archived_count'])
                    && isset($draft->metadata['threshold_date_formatted']);
            }));

        $response = ($this->useCase)(new ArchiveOldAuditLogsCommand(
            olderThanDays: 90,
            restaurantUuid: null,
            dryRun: false,
        ));

        $this->assertFalse($response->dryRun);
        $this->assertSame(11, $response->totalArchived);
        $this->assertSame($stats, $response->perRestaurant);
    }

    public function test_passes_restaurant_uuid_to_repository_when_provided(): void
    {
        $restaurantUuid = Uuid::generate();

        $this->repository
            ->shouldReceive('bulkArchive')
            ->once()
            ->with(
                Mockery::on(static fn ($id): bool => $id instanceof Uuid && $id->value() === $restaurantUuid->value()),
                Mockery::type(\DateTimeImmutable::class),
                false,
            )
            ->andReturn([]);

        $this->auditRecorder->shouldNotReceive('record');

        $response = ($this->useCase)(new ArchiveOldAuditLogsCommand(
            olderThanDays: 30,
            restaurantUuid: $restaurantUuid->value(),
            dryRun: false,
        ));

        $this->assertSame(0, $response->totalArchived);
    }

    public function test_propagates_dry_run_flag_to_repository(): void
    {
        $this->repository
            ->shouldReceive('bulkArchive')
            ->once()
            ->with(null, Mockery::type(\DateTimeImmutable::class), true)
            ->andReturn([]);

        $this->auditRecorder->shouldNotReceive('record');

        $response = ($this->useCase)(new ArchiveOldAuditLogsCommand(
            olderThanDays: 90,
            restaurantUuid: null,
            dryRun: true,
        ));

        $this->assertTrue($response->dryRun);
    }

    public function test_computes_threshold_based_on_older_than_days(): void
    {
        $this->repository
            ->shouldReceive('bulkArchive')
            ->once()
            ->withArgs(function ($restaurantId, $threshold, $dryRun): bool {
                $this->assertNull($restaurantId);
                $this->assertFalse($dryRun);
                $this->assertInstanceOf(\DateTimeImmutable::class, $threshold);

                $now = new \DateTimeImmutable;
                $expected = $now->modify('-30 days');
                // Tolerate a couple of seconds between use case execution and assertion.
                $this->assertLessThanOrEqual(2, abs($expected->getTimestamp() - $threshold->getTimestamp()));

                return true;
            })
            ->andReturn([]);

        $this->auditRecorder->shouldNotReceive('record');

        ($this->useCase)(new ArchiveOldAuditLogsCommand(
            olderThanDays: 30,
            restaurantUuid: null,
            dryRun: false,
        ));
    }

    public function test_rejects_non_positive_older_than_days(): void
    {
        $this->repository->shouldNotReceive('bulkArchive');

        $this->expectException(InvalidArchiveThresholdException::class);

        ($this->useCase)(new ArchiveOldAuditLogsCommand(
            olderThanDays: 0,
            restaurantUuid: null,
            dryRun: false,
        ));
    }

    public function test_response_to_array_serialises_per_restaurant_stats(): void
    {
        $restaurantId = Uuid::generate();
        $stats = [
            new ArchiveStats(
                restaurantId: $restaurantId,
                archivedCount: 3,
                oldestCreatedAt: new \DateTimeImmutable('2025-04-01 10:00:00'),
                newestCreatedAt: new \DateTimeImmutable('2025-04-30 10:00:00'),
            ),
        ];

        $this->repository
            ->shouldReceive('bulkArchive')
            ->once()
            ->andReturn($stats);

        $this->auditRecorder->shouldNotReceive('record');

        $response = ($this->useCase)(new ArchiveOldAuditLogsCommand(
            olderThanDays: 30,
            restaurantUuid: null,
            dryRun: true,
        ));

        $array = $response->toArray();
        $this->assertTrue($array['dry_run']);
        $this->assertSame(3, $array['total_archived']);
        $this->assertSame($restaurantId->value(), $array['per_restaurant'][0]['restaurant_uuid']);
        $this->assertSame(3, $array['per_restaurant'][0]['archived_count']);
        $this->assertNotNull($array['per_restaurant'][0]['oldest_created_at']);
        $this->assertNotNull($array['per_restaurant'][0]['newest_created_at']);
    }
}
