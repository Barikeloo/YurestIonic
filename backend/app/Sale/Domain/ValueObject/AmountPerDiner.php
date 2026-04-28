<?php

declare(strict_types=1);

namespace App\Sale\Domain\ValueObject;

final class AmountPerDiner
{
    private function __construct(private readonly int $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('Amount per diner cannot be negative');
        }
    }

    public static function create(int $totalCents, int $diners): self
    {
        if ($diners <= 0) {
            throw new \InvalidArgumentException('Diners count must be greater than 0');
        }

        // Floor division: each diner pays the base amount
        $baseAmount = (int) floor($totalCents / $diners);

        return new self($baseAmount);
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    /**
     * Calculate the amount for a specific diner number.
     * The last diner pays the remainder to ensure the sum equals total.
     */
    public function calculateForDiner(int $dinerNumber, int $totalDiners, int $totalCents): int
    {
        if ($dinerNumber < 1 || $dinerNumber > $totalDiners) {
            throw new \InvalidArgumentException('Invalid diner number');
        }

        // Last diner pays the remainder
        if ($dinerNumber === $totalDiners) {
            $sumOfPrevious = $this->value * ($totalDiners - 1);

            return $totalCents - $sumOfPrevious;
        }

        return $this->value;
    }
}
