<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetProductsReport;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\Interfaces\ReportingRepositoryInterface;

final readonly class GetProductsReport
{
    public function __construct(
        private ReportingRepositoryInterface $repository,
    ) {}

    public function __invoke(GetProductsReportCommand $command): GetProductsReportResponse
    {
        $range = DateRange::fromPeriod($command->period);

        $result = $this->repository->getProductsReport(
            restaurantId: $command->restaurantId,
            range:        $range,
        );

        return GetProductsReportResponse::create(
            periodRevenue: $result['period_revenue'],
            items:         $result['items'],
            stockCritical: $result['stock_critical'],
            noSales7d:     $result['no_sales_7d'],
            alertCount:    $result['alert_count'],
            byZone:        $result['by_zone'],
            periodLabel:   $range->label,
            restaurant:    $this->repository->getRestaurantInfo($command->restaurantId),
        );
    }
}
