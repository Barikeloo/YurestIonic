<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetEmployeesReport;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\Interfaces\ReportingRepositoryInterface;

final readonly class GetEmployeesReport
{
    public function __construct(
        private ReportingRepositoryInterface $repository,
    ) {}

    public function __invoke(GetEmployeesReportCommand $command): GetEmployeesReportResponse
    {
        $range = DateRange::fromPeriod($command->period);

        $result = $this->repository->getEmployeesReport(
            restaurantId: $command->restaurantId,
            range:        $range,
        );

        return GetEmployeesReportResponse::create(items: $result['items']);
    }
}
