<?php

declare(strict_types=1);

namespace App\Printer\Domain\ValueObject;

final readonly class PrinterIp
{
    private function __construct(private string $value) {}

    public static function create(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '' || strlen($trimmed) > 45) {
            throw new \InvalidArgumentException("Invalid printer IP: '{$trimmed}'");
        }

        return new self($trimmed);
    }

    public function value(): string
    {
        return $this->value;
    }
}
