<?php

declare(strict_types=1);

namespace App\Reporting\Application\DeleteScheduledReport;

use App\Reporting\Domain\Exception\ScheduledReportNotFoundException;
use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;

final readonly class DeleteScheduledReport
{
    public function __construct(
        private ScheduledReportRepositoryInterface $repository,
    ) {}

    public function __invoke(DeleteScheduledReportCommand $command): void
    {
        $existing = $this->repository->findByUuid($command->restaurantId, $command->uuid)
            ?? throw ScheduledReportNotFoundException::withUuid($command->uuid);

        $this->repository->delete($command->uuid);
    }
}
