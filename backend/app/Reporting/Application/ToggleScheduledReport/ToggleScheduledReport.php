<?php

declare(strict_types=1);

namespace App\Reporting\Application\ToggleScheduledReport;

use App\Reporting\Application\Shared\NextRunCalculator;
use App\Reporting\Domain\Exception\ScheduledReportNotFoundException;
use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;

final readonly class ToggleScheduledReport
{
    public function __construct(
        private ScheduledReportRepositoryInterface $repository,
    ) {}

    public function __invoke(ToggleScheduledReportCommand $command): array
    {
        $report = $this->repository->findByUuid($command->restaurantId, $command->uuid)
            ?? throw ScheduledReportNotFoundException::withUuid($command->uuid);

        $newActive = !$report['active'];

        $this->repository->setActive($command->uuid, $newActive);

        if ($newActive) {
            $now = new \DateTimeImmutable('now');
            $calculator = new NextRunCalculator($now);

            $nextRunAt = match ($report['frequency']) {
                'daily'     => $calculator->forDaily($report['time']),
                'weekly'    => $calculator->forWeekly($report['weekday'], $report['time']),
                'monthly'   => $calculator->forMonthly($report['day_of_month'], $report['time']),
                'quarterly' => $calculator->forQuarterly($report['time']),
            };

            $this->repository->update($command->uuid, [
                'next_run_at' => $nextRunAt->format('Y-m-d H:i:s'),
            ]);
        }

        return [
            'uuid'   => $report['uuid'],
            'active' => $newActive,
        ];
    }
}
