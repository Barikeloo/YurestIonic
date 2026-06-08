<?php

declare(strict_types=1);

namespace App\Menu\Domain\ValueObject;

use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use DateTimeImmutable;

final class MenuAvailability
{
    public const ALL_DAYS = 0b1111111;

    private const TIME_FORMAT = 'H:i:s';

    private function __construct(
        private int $daysBitmask,
        private ?string $fromTime,
        private ?string $toTime,
    ) {
        if ($daysBitmask < 0 || $daysBitmask > self::ALL_DAYS) {
            throw MenuInvalidConfigurationException::invalidDaysBitmask($daysBitmask);
        }

        if (($fromTime === null) !== ($toTime === null)) {
            throw MenuInvalidConfigurationException::partialTimeRange();
        }

        if ($fromTime !== null && $toTime !== null && $fromTime >= $toTime) {
            throw MenuInvalidConfigurationException::invalidTimeRange();
        }
    }

    public static function create(int $daysBitmask, ?string $fromTime, ?string $toTime): self
    {
        return new self(
            $daysBitmask,
            $fromTime !== null ? self::normalizeTime($fromTime) : null,
            $toTime !== null ? self::normalizeTime($toTime) : null,
        );
    }

    public static function alwaysAvailable(): self
    {
        return new self(self::ALL_DAYS, null, null);
    }

    public function daysBitmask(): int
    {
        return $this->daysBitmask;
    }

    public function fromTime(): ?string
    {
        return $this->fromTime;
    }

    public function toTime(): ?string
    {
        return $this->toTime;
    }

    public function isAllDay(): bool
    {
        return $this->fromTime === null && $this->toTime === null;
    }

    public function isAvailableOnWeekday(int $isoWeekday): bool
    {
        if ($isoWeekday < 1 || $isoWeekday > 7) {
            throw new \InvalidArgumentException('ISO weekday must be in 1..7');
        }
        $bit = 1 << ($isoWeekday - 1);

        return ($this->daysBitmask & $bit) === $bit;
    }

    public function isAvailableAt(DateTimeImmutable $instant): bool
    {
        if (! $this->isAvailableOnWeekday((int) $instant->format('N'))) {
            return false;
        }
        if ($this->isAllDay()) {
            return true;
        }
        $hms = $instant->format(self::TIME_FORMAT);

        return $hms >= $this->fromTime && $hms < $this->toTime;
    }

    private static function normalizeTime(string $time): string
    {

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time.':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        throw new \InvalidArgumentException("Invalid time format: {$time}");
    }
}
