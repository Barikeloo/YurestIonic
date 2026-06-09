<?php

namespace Tests\Unit\Reporting\Application;

use App\Reporting\Application\GetSalesReport\GetSalesReport;
use App\Reporting\Application\GetSalesReport\GetSalesReportCommand;
use App\Reporting\Application\GetSalesReport\GetSalesReportResponse;
use App\Reporting\Domain\Interfaces\ReportingRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class GetSalesReportTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_paginated_sales(): void
    {
        $repository = Mockery::mock(ReportingRepositoryInterface::class);

        $repository->shouldReceive('getSalesList')
            ->once()
            ->with(1, Mockery::on(fn ($r) => $r::class === 'App\Reporting\Application\Shared\DateRange'), 1, 50)
            ->andReturn([
                'data' => [
                    [
                        'uuid'            => '00000000-0000-4000-8000-000000000001',
                        'ticket_number'   => 42,
                        'value_date'      => '2026-06-09 14:23:00',
                        'total'           => 3420,
                        'status'          => 'closed',
                        'zone_name'       => 'Terraza',
                        'table_name'      => 'T-05',
                        'diners'          => 3,
                        'opened_by'       => 'María G.',
                        'tips_total'      => 150,
                        'payment_methods' => [
                            ['method' => 'card', 'amount' => 3000],
                            ['method' => 'cash', 'amount' => 420],
                        ],
                    ],
                ],
                'meta' => [
                    'total'     => 1,
                    'page'      => 1,
                    'per_page'  => 50,
                    'last_page' => 1,
                ],
                'totals' => [
                    'revenue' => 3420,
                    'cash'    => 420,
                    'card'    => 3000,
                    'bizum'   => 0,
                    'other'   => 0,
                    'tips'    => 150,
                ],
            ]);

        $useCase = new GetSalesReport($repository);

        $response = $useCase(new GetSalesReportCommand(
            restaurantId: 1,
            period:       'today',
            page:         1,
            perPage:      50,
        ));

        $this->assertInstanceOf(GetSalesReportResponse::class, $response);

        $arr = $response->toArray();
        $this->assertCount(1, $arr['data']);
        $this->assertSame('00000000-0000-4000-8000-000000000001', $arr['data'][0]['uuid']);
        $this->assertSame(42, $arr['data'][0]['ticket_number']);
        $this->assertSame(3420, $arr['data'][0]['total']);
        $this->assertSame(1, $arr['meta']['total']);
        $this->assertSame(1, $arr['meta']['page']);
        $this->assertSame(3420, $arr['totals']['revenue']);
    }

    public function test_returns_empty_list_when_no_sales(): void
    {
        $repository = Mockery::mock(ReportingRepositoryInterface::class);

        $repository->shouldReceive('getSalesList')
            ->once()
            ->andReturn([
                'data'   => [],
                'meta'   => ['total' => 0, 'page' => 1, 'per_page' => 50, 'last_page' => 1],
                'totals' => ['revenue' => 0, 'cash' => 0, 'card' => 0, 'bizum' => 0, 'other' => 0, 'tips' => 0],
            ]);

        $useCase = new GetSalesReport($repository);

        $response = $useCase(new GetSalesReportCommand(
            restaurantId: 1,
            period:       'today',
            page:         1,
            perPage:      50,
        ));

        $arr = $response->toArray();
        $this->assertEmpty($arr['data']);
        $this->assertSame(0, $arr['meta']['total']);
        $this->assertSame(0, $arr['totals']['revenue']);
    }

    public function test_handles_page_and_per_page(): void
    {
        $repository = Mockery::mock(ReportingRepositoryInterface::class);

        $repository->shouldReceive('getSalesList')
            ->once()
            ->with(1, Mockery::any(), 2, 10)
            ->andReturn([
                'data'   => [],
                'meta'   => ['total' => 0, 'page' => 2, 'per_page' => 10, 'last_page' => 1],
                'totals' => ['revenue' => 0, 'cash' => 0, 'card' => 0, 'bizum' => 0, 'other' => 0, 'tips' => 0],
            ]);

        $useCase = new GetSalesReport($repository);

        $response = $useCase(new GetSalesReportCommand(
            restaurantId: 1,
            period:       'week',
            page:         2,
            perPage:      10,
        ));

        $arr = $response->toArray();
        $this->assertSame(2, $arr['meta']['page']);
        $this->assertSame(10, $arr['meta']['per_page']);
    }
}
