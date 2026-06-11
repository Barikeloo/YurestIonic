<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetFamiliesReport;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\Interfaces\ReportingRepositoryInterface;

final readonly class GetFamiliesReport
{
    public function __construct(
        private ReportingRepositoryInterface $repository,
    ) {}

    public function __invoke(GetFamiliesReportCommand $command): GetFamiliesReportResponse
    {
        $range  = DateRange::fromPeriod($command->period);
        $result = $this->repository->getFamiliesReport($command->restaurantId, $range);

        $families = $result['families'] ?? [];
        $total    = array_sum(array_column($families, 'revenue'));

        return GetFamiliesReportResponse::create(
            restaurant:  $this->repository->getRestaurantInfo($command->restaurantId),
            periodLabel: $range->label,
            families:    $families,
            total:       $total,
            prevTotal:   (int) ($result['prev_total'] ?? 0),
        );
    }
}
