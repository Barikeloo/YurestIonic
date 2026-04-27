<?php

declare(strict_types=1);

namespace App\Cash\Domain\ValueObject;

final class ZReportHash
{
    private const PATTERN = '/^[0-9a-f]{64}$/';

    private function __construct(
        private readonly string $value,
    ) {
        if (! preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException('Z-Report hash must be a 64-character hexadecimal sha256.');
        }
    }

    public static function create(string $value): self
    {
        return new self(strtolower($value));
    }

    public function value(): string
    {
        return $this->value;
    }
}
