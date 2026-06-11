<?php

namespace Tests\Unit\Reporting\Application;

use App\Reporting\Application\ListScheduledReports\ListScheduledReports;
use App\Reporting\Application\ListScheduledReports\ListScheduledReportsCommand;
use App\Reporting\Application\ListScheduledReports\ListScheduledReportsResponse;
use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ListScheduledReportsTest extends TestCase
{
    private ScheduledReportRepositoryInterface&MockInterface $repository;
    private ListScheduledReports $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ScheduledReportRepositoryInterface::class);
        $this->useCase = new ListScheduledReports($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_all_reports_for_restaurant(): void
    {
        $reports = [
            ['uuid' => 'uuid-1', 'name' => 'Daily Report', 'report_type' => 'daily', 'active' => true],
            ['uuid' => 'uuid-2', 'name' => 'Weekly Report', 'report_type' => 'products', 'active' => false],
        ];

        $this->repository
            ->shouldReceive('listForRestaurant')
            ->once()
            ->with(1)
            ->andReturn($reports);

        $response = ($this->useCase)(new ListScheduledReportsCommand(restaurantId: 1));

        $this->assertInstanceOf(ListScheduledReportsResponse::class, $response);
        $this->assertCount(2, $response->reports);
        $this->assertSame($reports, $response->toArray());
    }

    public function test_returns_empty_list_when_no_reports(): void
    {
        $this->repository
            ->shouldReceive('listForRestaurant')
            ->once()
            ->with(1)
            ->andReturn([]);

        $response = ($this->useCase)(new ListScheduledReportsCommand(restaurantId: 1));

        $this->assertEmpty($response->reports);
        $this->assertEmpty($response->toArray());
    }
}
