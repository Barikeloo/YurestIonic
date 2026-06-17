<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetEmployeesReport;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\ReadModel\EmployeesReadRepositoryInterface;
use App\Reporting\Domain\ReadModel\RestaurantInfoReadRepositoryInterface;

final readonly class GetEmployeesReport
{
    public function __construct(
        private EmployeesReadRepositoryInterface $employees,
        private RestaurantInfoReadRepositoryInterface $restaurantInfo,
    ) {}

    public function __invoke(GetEmployeesReportCommand $command): GetEmployeesReportResponse
    {
        $range = DateRange::fromPeriod($command->period);

        $result = $this->employees->getEmployeesReport(
            restaurantId: $command->restaurantId,
            range:        $range,
        );

        return GetEmployeesReportResponse::create(
            items:       $result['items'],
            periodLabel: $range->label,
            restaurant:  $this->restaurantInfo->getRestaurantInfo($command->restaurantId),
        );
    }
}
