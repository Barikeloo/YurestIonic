<?php

declare(strict_types=1);

namespace App\Printer\Domain\ValueObject;

final readonly class PrinterPort
{
    private function __construct(private int $value) {}

    public static function create(int $value): self
    {
        if ($value < 1 || $value > 65535) {
            throw new \InvalidArgumentException("Invalid printer port: {$value}. Must be 1-65535.");
        }

        return new self($value);
    }

    public static function default(): self
    {
        return new self(9100);
    }

    public function value(): int
    {
        return $this->value;
    }
}
