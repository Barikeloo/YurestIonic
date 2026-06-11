<?php

declare(strict_types=1);

namespace App\Reporting\Application\UpdateScheduledReport;

use App\Reporting\Application\Shared\NextRunCalculator;
use App\Reporting\Domain\Exception\InvalidScheduleException;
use App\Reporting\Domain\Exception\ScheduledReportNotFoundException;
use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;

final readonly class UpdateScheduledReport
{
    public function __construct(
        private ScheduledReportRepositoryInterface $repository,
    ) {}

    public function __invoke(UpdateScheduledReportCommand $command): void
    {
        $existing = $this->repository->findByUuid($command->restaurantId, $command->uuid)
            ?? throw ScheduledReportNotFoundException::withUuid($command->uuid);

        $this->validate($command);

        $now = new \DateTimeImmutable('now');
        $calculator = new NextRunCalculator($now);

        $nextRunAt = $this->calculateNextRun($command, $calculator);

        $this->repository->update($command->uuid, [
            'report_type'  => $command->reportType,
            'format'        => $command->format,
            'frequency'     => $command->frequency,
            'time'          => $command->time,
            'weekday'       => $command->weekday,
            'day_of_month'  => $command->dayOfMonth,
            'recipients'    => $command->recipients,
            'name'          => $command->name,
            'active'        => $command->active,
            'next_run_at'   => $nextRunAt->format('Y-m-d H:i:s'),
        ]);
    }

    private function validate(UpdateScheduledReportCommand $command): void
    {
        $validTypes = ['daily', 'products', 'families', 'cash', 'tips', 'taxes'];
        if (!in_array($command->reportType, $validTypes, true)) {
            throw InvalidScheduleException::because("Invalid report type: {$command->reportType}");
        }

        if (!in_array($command->format, ['PDF', 'CSV'], true)) {
            throw InvalidScheduleException::because("Invalid format: {$command->format}");
        }

        if (!in_array($command->frequency, ['daily', 'weekly', 'monthly', 'quarterly'], true)) {
            throw InvalidScheduleException::because("Invalid frequency: {$command->frequency}");
        }

        if ($command->frequency === 'weekly' && ($command->weekday === null || $command->weekday < 1 || $command->weekday > 7)) {
            throw InvalidScheduleException::because('Weekday (1-7) is required for weekly frequency');
        }

        if ($command->frequency === 'monthly' && ($command->dayOfMonth === null || $command->dayOfMonth < 1 || $command->dayOfMonth > 28)) {
            throw InvalidScheduleException::because('Day of month (1-28) is required for monthly frequency');
        }

        if (empty($command->recipients)) {
            throw InvalidScheduleException::because('At least one recipient is required');
        }
    }

    private function calculateNextRun(UpdateScheduledReportCommand $command, NextRunCalculator $calculator): \DateTimeImmutable
    {
        if (!$command->active) {
            return new \DateTimeImmutable('9999-12-31 23:59:59');
        }

        return match ($command->frequency) {
            'daily'     => $calculator->forDaily($command->time),
            'weekly'    => $calculator->forWeekly($command->weekday, $command->time),
            'monthly'   => $calculator->forMonthly($command->dayOfMonth, $command->time),
            'quarterly' => $calculator->forQuarterly($command->time),
        };
    }
}
