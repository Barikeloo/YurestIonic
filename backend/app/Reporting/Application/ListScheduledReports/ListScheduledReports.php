<?php

declare(strict_types=1);

namespace App\Reporting\Application\ListScheduledReports;

use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;

final readonly class ListScheduledReports
{
    public function __construct(
        private ScheduledReportRepositoryInterface $repository,
    ) {}

    public function __invoke(ListScheduledReportsCommand $command): ListScheduledReportsResponse
    {
        $reports = $this->repository->listForRestaurant($command->restaurantId);

        return ListScheduledReportsResponse::create($reports);
    }
}
