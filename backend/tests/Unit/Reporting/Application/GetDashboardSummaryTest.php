<?php

namespace Tests\Unit\Reporting\Application;

use App\Reporting\Application\GetDashboardSummary\GetDashboardSummary;
use App\Reporting\Application\GetDashboardSummary\GetDashboardSummaryCommand;
use App\Reporting\Domain\Interfaces\ReportingRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class GetDashboardSummaryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ReportingRepositoryInterface&MockInterface $repository;
    private GetDashboardSummary $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ReportingRepositoryInterface::class);
        $this->useCase = new GetDashboardSummary($this->repository);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function runWith(array $overrides = []): array
    {
        $data = array_merge([
            'revenue' => 0, 'tickets' => 0, 'items_sold' => 0, 'diners' => 0,
            'prev_revenue' => 0, 'prev_tickets' => 0, 'prev_items_sold' => 0, 'prev_diners' => 0,
            'day_totals' => [], 'day_items' => [],
            'by_hour' => [], 'by_hour_prev' => [],
            'by_family' => [], 'top_products' => [], 'by_payment_method' => [],
        ], $overrides);

        $this->repository->shouldReceive('getDashboardData')->once()->andReturn($data);

        return ($this->useCase)(new GetDashboardSummaryCommand(restaurantId: 1, period: 'today'))->toArray();
    }

    public function test_computes_kpis_with_deltas_and_average_ticket(): void
    {
        $result = $this->runWith([
            'revenue' => 10000, 'tickets' => 20, 'items_sold' => 80, 'diners' => 30,
            'prev_revenue' => 8000, 'prev_tickets' => 16, 'prev_items_sold' => 64, 'prev_diners' => 25,
        ]);

        $this->assertSame(10000, $result['kpis']['revenue']['v']);
        $this->assertSame(8000, $result['kpis']['revenue']['prev']);
        $this->assertSame(25.0, $result['kpis']['revenue']['delta_pct']);

        // avg_ticket = intdiv(10000, 20) = 500, prev = intdiv(8000, 16) = 500 -> 0% delta
        $this->assertSame(500, $result['kpis']['avg_ticket']['v']);
        $this->assertSame(500, $result['kpis']['avg_ticket']['prev']);
        $this->assertSame(0.0, $result['kpis']['avg_ticket']['delta_pct']);
    }

    public function test_guards_division_by_zero(): void
    {
        $result = $this->runWith([
            'revenue' => 5000, 'tickets' => 0, // avg_ticket must not divide by zero
            'prev_revenue' => 0, 'prev_tickets' => 0,
        ]);

        $this->assertSame(0, $result['kpis']['avg_ticket']['v']);
        // prev revenue is 0 -> delta_pct falls back to 0.0 instead of dividing by zero
        $this->assertSame(0.0, $result['kpis']['revenue']['delta_pct']);
    }

    public function test_fills_business_hours_8_to_23_zero_filling_gaps(): void
    {
        $result = $this->runWith([
            'by_hour' => [['h' => 13, 'v' => 4200, 'n' => 7]],
        ]);

        $byHour = $result['by_hour'];
        $this->assertCount(16, $byHour); // 08..23 inclusive
        $this->assertSame('08', $byHour[0]['l']);
        $this->assertSame('23', $byHour[15]['l']);

        // hour 13 is index 5 (13 - 8) and carries the provided values
        $this->assertSame('13', $byHour[5]['l']);
        $this->assertSame(4200, $byHour[5]['v']);
        $this->assertSame(7, $byHour[5]['n']);

        // an untouched hour is zero-filled
        $this->assertSame(0, $byHour[0]['v']);
        $this->assertSame(0, $byHour[0]['n']);
    }

    public function test_builds_14_day_sparks_and_byday_with_todays_values(): void
    {
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');

        $result = $this->runWith([
            'day_totals' => [$today => ['v' => 6000, 'n' => 12]],
            'day_items'  => [$today => 48],
        ]);

        // 14-day windows
        $this->assertCount(14, $result['sparks']['revenue']);
        $this->assertCount(14, $result['by_day']);

        // last entry (i = 0) is today
        $this->assertSame(6000, $result['sparks']['revenue'][13]);
        $this->assertSame(12, $result['sparks']['tickets'][13]);
        $this->assertSame(500, $result['sparks']['avg_ticket'][13]); // intdiv(6000, 12)
        $this->assertSame(48, $result['sparks']['items'][13]);
        $this->assertSame(6000, $result['by_day'][13]['v']);
    }

    public function test_passes_through_breakdown_arrays(): void
    {
        $byFamily = [['name' => 'Bebidas', 'revenue' => 1000]];
        $topProducts = [['name' => 'Caña', 'units' => 50]];
        $byPayment = [['method' => 'cash', 'total' => 3000]];

        $result = $this->runWith([
            'by_family' => $byFamily,
            'top_products' => $topProducts,
            'by_payment_method' => $byPayment,
        ]);

        $this->assertSame($byFamily, $result['by_family']);
        $this->assertSame($topProducts, $result['top_products']);
        $this->assertSame($byPayment, $result['by_payment_method']);
    }
}
