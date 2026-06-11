<?php

declare(strict_types=1);

namespace App\Reporting\Application\Shared;

final readonly class NextRunCalculator
{
    public function __construct(
        private \DateTimeImmutable $now,
    ) {}

    public function forDaily(string $time): \DateTimeImmutable
    {
        $target = $this->buildDate($this->now->format('Y-m-d'), $time);

        if ($target > $this->now) {
            return $target;
        }

        return $target->modify('+1 day');
    }

    public function forWeekly(int $weekday, string $time): \DateTimeImmutable
    {
        $target = $this->buildDate($this->now->format('Y-m-d'), $time);
        $currentWeekday = (int) $this->now->format('N');

        $daysUntil = ($weekday - $currentWeekday + 7) % 7;

        if ($daysUntil === 0 && $target > $this->now) {
            return $target;
        }

        if ($daysUntil === 0) {
            $daysUntil = 7;
        }

        return $target->modify("+{$daysUntil} days");
    }

    public function forMonthly(int $dayOfMonth, string $time): \DateTimeImmutable
    {
        $year = (int) $this->now->format('Y');
        $month = (int) $this->now->format('m');

        $target = $this->buildDate(sprintf('%04d-%02d-%02d', $year, $month, min($dayOfMonth, $this->daysInMonth($year, $month))), $time);

        if ($target > $this->now) {
            return $target;
        }

        $nextMonth = $month === 12 ? 1 : $month + 1;
        $nextYear = $month === 12 ? $year + 1 : $year;

        $clampedDay = min($dayOfMonth, $this->daysInMonth($nextYear, $nextMonth));

        return $this->buildDate(sprintf('%04d-%02d-%02d', $nextYear, $nextMonth, $clampedDay), $time);
    }

    public function forQuarterly(string $time): \DateTimeImmutable
    {
        $month = (int) $this->now->format('n');
        $year = (int) $this->now->format('Y');

        [$nextYear, $nextMonth] = match (true) {
            $month <= 3  => [$year, 4],
            $month <= 6  => [$year, 7],
            $month <= 9  => [$year, 10],
            default      => [$year + 1, 1],
        };

        return $this->buildDate(sprintf('%04d-%02d-01', $nextYear, $nextMonth), $time);
    }

    private function buildDate(string $date, string $time): \DateTimeImmutable
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i', "{$date} {$time}");

        if ($dt === false) {
            throw new \InvalidArgumentException("Invalid date/time: {$date} {$time}");
        }

        return $dt;
    }

    private function daysInMonth(int $year, int $month): int
    {
        return (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
    }
}
