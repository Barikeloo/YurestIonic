<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetSalesReport;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\Interfaces\ReportingRepositoryInterface;

final readonly class GetSalesReport
{
    public function __construct(
        private ReportingRepositoryInterface $repository,
    ) {}

    public function __invoke(GetSalesReportCommand $command): GetSalesReportResponse
    {
        $range = DateRange::fromPeriod($command->period);

        $result = $this->repository->getSalesList(
            restaurantId: $command->restaurantId,
            range:        $range,
            page:         $command->page,
            perPage:      $command->perPage,
        );

        return GetSalesReportResponse::create(
            data:   $result['data'],
            meta:   $result['meta'],
            totals: $result['totals'],
        );
    }
}
