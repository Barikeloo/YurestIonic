<?php

namespace Tests\Unit\Reporting\Application;

use App\Reporting\Application\GetFamiliesReport\GetFamiliesReport;
use App\Reporting\Application\GetFamiliesReport\GetFamiliesReportCommand;
use App\Reporting\Domain\Interfaces\ReportingRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class GetFamiliesReportTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ReportingRepositoryInterface&MockInterface $repository;
    private GetFamiliesReport $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ReportingRepositoryInterface::class);
        $this->useCase = new GetFamiliesReport($this->repository);
        $this->repository->shouldReceive('getRestaurantInfo')->andReturn(['name' => 'Test']);
    }

    public function test_sums_family_revenue_into_total(): void
    {
        $families = [
            ['name' => 'Bebidas', 'revenue' => 1500],
            ['name' => 'Postres', 'revenue' => 500],
            ['name' => 'Platos',  'revenue' => 3000],
        ];

        $this->repository->shouldReceive('getFamiliesReport')->once()
            ->andReturn(['families' => $families, 'prev_total' => 4200]);

        $result = ($this->useCase)(new GetFamiliesReportCommand(restaurantId: 1, period: 'month'))->toArray();

        $this->assertSame(5000, $result['total']);
        $this->assertSame(4200, $result['prev_total']);
        $this->assertSame($families, $result['families']);
    }

    public function test_defaults_to_zero_when_repository_returns_empty(): void
    {
        $this->repository->shouldReceive('getFamiliesReport')->once()->andReturn([]);

        $result = ($this->useCase)(new GetFamiliesReportCommand(restaurantId: 1, period: 'today'))->toArray();

        $this->assertSame(0, $result['total']);
        $this->assertSame(0, $result['prev_total']);
        $this->assertSame([], $result['families']);
    }
}
