<?php

declare(strict_types=1);

namespace App\Cash\Domain\ValueObject;

final class ZReportNumber
{
    private const MIN = 1;

    private function __construct(
        private readonly int $value,
    ) {
        if ($value < self::MIN) {
            throw new \InvalidArgumentException('Z-Report number must be greater than or equal to 1.');
        }
    }

    public static function create(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }
}
