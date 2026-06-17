<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetFamiliesReport;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\ReadModel\FamiliesReadRepositoryInterface;
use App\Reporting\Domain\ReadModel\RestaurantInfoReadRepositoryInterface;

final readonly class GetFamiliesReport
{
    public function __construct(
        private FamiliesReadRepositoryInterface $families,
        private RestaurantInfoReadRepositoryInterface $restaurantInfo,
    ) {}

    public function __invoke(GetFamiliesReportCommand $command): GetFamiliesReportResponse
    {
        $range  = DateRange::fromPeriod($command->period);
        $result = $this->families->getFamiliesReport($command->restaurantId, $range);

        $families = $result['families'] ?? [];
        $total    = array_sum(array_column($families, 'revenue'));

        return GetFamiliesReportResponse::create(
            restaurant:  $this->restaurantInfo->getRestaurantInfo($command->restaurantId),
            periodLabel: $range->label,
            families:    $families,
            total:       $total,
            prevTotal:   (int) ($result['prev_total'] ?? 0),
        );
    }
}
