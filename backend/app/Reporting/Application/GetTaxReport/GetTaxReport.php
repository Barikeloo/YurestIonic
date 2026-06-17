<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetTaxReport;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\ReadModel\TaxReadRepositoryInterface;

final readonly class GetTaxReport
{
    public function __construct(
        private TaxReadRepositoryInterface $repository,
    ) {}

    public function __invoke(GetTaxReportCommand $command): GetTaxReportResponse
    {
        $range   = DateRange::fromPeriod($command->period);
        $year    = (int) (new \DateTimeImmutable('now'))->format('Y');
        $qRange  = DateRange::forQuarter($year, $command->quarter);

        $result = $this->repository->getTaxReport(
            restaurantId: $command->restaurantId,
            range:        $range,
            qRange:       $qRange,
            quarter:      $command->quarter,
            year:         $year,
        );

        return GetTaxReportResponse::create($result);
    }
}
