<?php

namespace Tests\Unit\Audit\Application;

use App\Audit\Application\GetArchivedAuditStats\GetArchivedAuditStats;
use App\Audit\Application\GetArchivedAuditStats\GetArchivedAuditStatsCommand;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\ArchivedAuditStats;
use App\Audit\Domain\ValueObject\MonthlyArchivedCount;
use App\Shared\Domain\ValueObject\Uuid;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class GetArchivedAuditStatsTest extends TestCase
{
    private AuditLogRepositoryInterface&MockInterface $repository;
    private CacheRepository $cache;
    private GetArchivedAuditStats $useCase;
    private string $restaurantUuid;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AuditLogRepositoryInterface::class);
        $this->cache = new CacheRepository(new ArrayStore);
        $this->useCase = new GetArchivedAuditStats($this->repository, $this->cache);
        $this->restaurantUuid = Uuid::generate()->value();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_passes_restaurant_uuid_and_null_dates_when_command_has_no_range(): void
    {
        $this->repository
            ->shouldReceive('getArchivedStats')
            ->once()
            ->with(Mockery::on(fn (Uuid $u) => $u->value() === $this->restaurantUuid), null, null)
            ->andReturn(ArchivedAuditStats::empty());

        $response = ($this->useCase)(new GetArchivedAuditStatsCommand($this->restaurantUuid));

        $this->assertSame(0, $response->toArray()['total']);
    }

    public function test_parses_date_from_and_sets_date_to_to_end_of_day(): void
    {
        $capturedFrom = null;
        $capturedTo = null;
        $this->repository
            ->shouldReceive('getArchivedStats')
            ->once()
            ->with(
                Mockery::type(Uuid::class),
                Mockery::on(static function (\DateTimeImmutable $d) use (&$capturedFrom): bool {
                    $capturedFrom = $d;
                    return true;
                }),
                Mockery::on(static function (\DateTimeImmutable $d) use (&$capturedTo): bool {
                    $capturedTo = $d;
                    return true;
                }),
            )
            ->andReturn(ArchivedAuditStats::empty());

        ($this->useCase)(new GetArchivedAuditStatsCommand(
            restaurantId: $this->restaurantUuid,
            dateFrom: '2025-01-15',
            dateTo: '2025-03-31',
        ));

        $this->assertSame('2025-01-15 00:00:00', $capturedFrom?->format('Y-m-d H:i:s'));
        $this->assertSame('2025-03-31 23:59:59', $capturedTo?->format('Y-m-d H:i:s'));
    }

    public function test_caches_second_call_with_same_command(): void
    {
        $this->repository
            ->shouldReceive('getArchivedStats')
            ->once()
            ->andReturn(new ArchivedAuditStats(
                total: 5,
                oldestCreatedAt: new \DateTimeImmutable('2025-01-01 09:00:00'),
                newestCreatedAt: new \DateTimeImmutable('2025-03-01 09:00:00'),
                monthlyBreakdown: [new MonthlyArchivedCount('2025-01', 3), new MonthlyArchivedCount('2025-03', 2)],
            ));

        $first = ($this->useCase)(new GetArchivedAuditStatsCommand($this->restaurantUuid));
        $second = ($this->useCase)(new GetArchivedAuditStatsCommand($this->restaurantUuid));

        $this->assertSame(5, $first->toArray()['total']);
        $this->assertSame(5, $second->toArray()['total']);
    }

    public function test_different_date_ranges_use_distinct_cache_keys(): void
    {
        $calls = 0;
        $this->repository
            ->shouldReceive('getArchivedStats')
            ->twice()
            ->andReturnUsing(function () use (&$calls) {
                $calls++;
                return ArchivedAuditStats::empty();
            });

        ($this->useCase)(new GetArchivedAuditStatsCommand($this->restaurantUuid, '2025-01-01', '2025-03-31'));
        ($this->useCase)(new GetArchivedAuditStatsCommand($this->restaurantUuid, '2025-04-01', '2025-06-30'));

        $this->assertSame(2, $calls, 'each distinct range must hit the repository');
    }

    public function test_cache_key_helper_distinguishes_ranges(): void
    {
        $restaurantId = Uuid::create($this->restaurantUuid);

        $keyAll = GetArchivedAuditStats::cacheKey($restaurantId);
        $keyRange = GetArchivedAuditStats::cacheKey($restaurantId, '2025-01-01', '2025-03-31');
        $keyOther = GetArchivedAuditStats::cacheKey($restaurantId, '2025-04-01', '2025-06-30');

        $this->assertNotSame($keyAll, $keyRange);
        $this->assertNotSame($keyRange, $keyOther);
        $this->assertStringStartsWith('audit:archived-stats:'.$this->restaurantUuid, $keyAll);
    }

    public function test_serialises_full_stats_into_response_payload(): void
    {
        $this->repository
            ->shouldReceive('getArchivedStats')
            ->once()
            ->andReturn(new ArchivedAuditStats(
                total: 8,
                oldestCreatedAt: new \DateTimeImmutable('2025-01-15 09:00:00'),
                newestCreatedAt: new \DateTimeImmutable('2025-03-25 18:00:00'),
                monthlyBreakdown: [
                    new MonthlyArchivedCount('2025-01', 3),
                    new MonthlyArchivedCount('2025-03', 5),
                ],
            ));

        $response = ($this->useCase)(new GetArchivedAuditStatsCommand($this->restaurantUuid));
        $payload = $response->toArray();

        $this->assertSame(8, $payload['total']);
        $this->assertSame('2025-01-15T09:00:00+00:00', $payload['oldest_created_at']);
        $this->assertSame('2025-03-25T18:00:00+00:00', $payload['newest_created_at']);
        $this->assertSame([
            ['month' => '2025-01', 'count' => 3],
            ['month' => '2025-03', 'count' => 5],
        ], $payload['monthly_breakdown']);
    }
}
