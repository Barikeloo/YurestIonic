<?php

namespace Tests\Unit\Cash\Application;

use App\Cash\Application\GetZReport\GetZReport;
use App\Cash\Application\GetZReport\GetZReportCommand;
use App\Cash\Domain\Entity\ZReport;
use App\Cash\Domain\Exception\ZReportNotFoundException;
use App\Cash\Domain\Interfaces\ZReportRepositoryInterface;
use App\Cash\Domain\ValueObject\ZReportNumber;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class GetZReportTest extends TestCase
{
    private ZReportRepositoryInterface&MockInterface $zReportRepository;
    private GetZReport $useCase;

    protected function setUp(): void
    {
        $this->zReportRepository = Mockery::mock(ZReportRepositoryInterface::class);
        $this->useCase = new GetZReport($this->zReportRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_z_report(): void
    {
        $zReportId = Uuid::generate()->value();

        $command = new GetZReportCommand(zReportId: $zReportId);

        $zReport = ZReport::generate(
            restaurantId: Uuid::generate(),
            cashSessionId: Uuid::generate(),
            reportNumber: ZReportNumber::create(1),
            totalSales: Money::create(100000),
            totalCash: Money::create(70000),
            totalCard: Money::create(30000),
            totalOther: Money::create(0),
            cashIn: Money::create(50000),
            cashOut: Money::create(10000),
            tips: Money::create(5000),
            discrepancy: Money::create(0),
            salesCount: 10,
            cancelledSalesCount: 1,
        );

        $this->zReportRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($zReport);

        $response = ($this->useCase)($command);

        $this->assertSame($zReport->id()->value(), $response->id);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $command = new GetZReportCommand(zReportId: Uuid::generate()->value());

        $this->zReportRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(null);

        $this->expectException(ZReportNotFoundException::class);

        ($this->useCase)($command);
    }
}
