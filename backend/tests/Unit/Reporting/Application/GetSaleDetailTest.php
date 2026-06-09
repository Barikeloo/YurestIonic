<?php

namespace Tests\Unit\Reporting\Application;

use App\Reporting\Application\GetSaleDetail\GetSaleDetail;
use App\Reporting\Application\GetSaleDetail\GetSaleDetailCommand;
use App\Reporting\Application\GetSaleDetail\GetSaleDetailResponse;
use App\Reporting\Domain\Interfaces\ReportingRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class GetSaleDetailTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_sale_detail(): void
    {
        $repository = Mockery::mock(ReportingRepositoryInterface::class);

        $repository->shouldReceive('getSaleDetail')
            ->once()
            ->with(1, '00000000-0000-4000-8000-000000000001')
            ->andReturn([
                'uuid'             => '00000000-0000-4000-8000-000000000001',
                'ticket_number'    => 42,
                'value_date'       => '2026-06-09 14:23:00',
                'status'           => 'closed',
                'zone_name'        => 'Terraza',
                'table_name'       => 'T-05',
                'diners'           => 3,
                'opened_by'        => 'María G.',
                'duration_minutes' => 67,
                'lines'            => [
                    ['product_name' => 'Caña', 'family_name' => 'Bebidas', 'qty' => 2, 'unit_price' => 180, 'tax_pct' => 10, 'total' => 360],
                ],
                'payments' => [
                    ['method' => 'card', 'amount' => 3000],
                ],
                'tax_breakdown' => [
                    ['rate' => 10, 'base' => 3109, 'tax' => 311],
                ],
                'subtotal'      => 3109,
                'tax_total'     => 311,
                'tips_total'    => 150,
                'cancel_reason' => null,
            ]);

        $useCase = new GetSaleDetail($repository);

        $response = $useCase(new GetSaleDetailCommand(
            restaurantId: 1,
            saleUuid:    '00000000-0000-4000-8000-000000000001',
        ));

        $this->assertInstanceOf(GetSaleDetailResponse::class, $response);

        $arr = $response->toArray();
        $this->assertSame('00000000-0000-4000-8000-000000000001', $arr['uuid']);
        $this->assertSame(42, $arr['ticket_number']);
        $this->assertSame('closed', $arr['status']);
        $this->assertCount(1, $arr['lines']);
        $this->assertCount(1, $arr['payments']);
        $this->assertCount(1, $arr['tax_breakdown']);
        $this->assertSame(150, $arr['tips_total']);
    }

    public function test_throws_exception_when_sale_not_found(): void
    {
        $repository = Mockery::mock(ReportingRepositoryInterface::class);

        $repository->shouldReceive('getSaleDetail')
            ->once()
            ->with(1, '00000000-0000-4000-8000-000000009999')
            ->andReturn(null);

        $useCase = new GetSaleDetail($repository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Sale not found.');

        $useCase(new GetSaleDetailCommand(
            restaurantId: 1,
            saleUuid:    '00000000-0000-4000-8000-000000009999',
        ));
    }

    public function test_returns_cancelled_sale_detail(): void
    {
        $repository = Mockery::mock(ReportingRepositoryInterface::class);

        $repository->shouldReceive('getSaleDetail')
            ->once()
            ->with(1, '00000000-0000-4000-8000-000000000002')
            ->andReturn([
                'uuid'             => '00000000-0000-4000-8000-000000000002',
                'ticket_number'    => 43,
                'value_date'       => '2026-06-09 12:00:00',
                'status'           => 'cancelled',
                'zone_name'        => 'Barra',
                'table_name'       => '—',
                'diners'           => 1,
                'opened_by'        => 'Carlos L.',
                'duration_minutes' => null,
                'lines'            => [],
                'payments'         => [],
                'tax_breakdown'    => [],
                'subtotal'         => 0,
                'tax_total'        => 0,
                'tips_total'       => 0,
                'cancel_reason'    => 'Cliente insatisfecho',
            ]);

        $useCase = new GetSaleDetail($repository);

        $response = $useCase(new GetSaleDetailCommand(
            restaurantId: 1,
            saleUuid:    '00000000-0000-4000-8000-000000000002',
        ));

        $arr = $response->toArray();
        $this->assertSame('cancelled', $arr['status']);
        $this->assertSame('Cliente insatisfecho', $arr['cancel_reason']);
        $this->assertEmpty($arr['lines']);
    }
}
