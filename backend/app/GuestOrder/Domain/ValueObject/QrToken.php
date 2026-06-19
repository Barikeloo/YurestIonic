<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ValueObject;

final class QrToken
{
    private string $value;

    private function __construct(string $value)
    {
        if (strlen($value) !== 64 || ! ctype_xdigit($value)) {
            throw new \InvalidArgumentException("Invalid QR token: must be a 64-character hex string.");
        }

        $this->value = strtolower($value);
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(32)));
    }

    public function value(): string
    {
        return $this->value;
    }
}
