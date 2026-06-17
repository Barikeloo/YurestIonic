<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetCashReport;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\ReadModel\CashReadRepositoryInterface;
use App\Reporting\Domain\ReadModel\RestaurantInfoReadRepositoryInterface;

final readonly class GetCashReport
{
    public function __construct(
        private CashReadRepositoryInterface $cash,
        private RestaurantInfoReadRepositoryInterface $restaurantInfo,
    ) {}

    public function __invoke(GetCashReportCommand $command): GetCashReportResponse
    {
        $range  = DateRange::fromPeriod($command->period);
        $result = $this->cash->getCashReport($command->restaurantId, $range);

        return GetCashReportResponse::create(
            restaurant:       $this->restaurantInfo->getRestaurantInfo($command->restaurantId),
            periodLabel:      $range->label,
            sessions:         $result['sessions'] ?? [],
            movements:        $result['movements'] ?? [],
            totalIn:          (int) ($result['total_in'] ?? 0),
            totalOut:         (int) ($result['total_out'] ?? 0),
            net:              (int) ($result['net'] ?? 0),
            discrepancyTotal: (int) ($result['discrepancy_total'] ?? 0),
        );
    }
}
