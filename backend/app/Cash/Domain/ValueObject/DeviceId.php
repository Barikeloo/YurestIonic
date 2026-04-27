<?php

declare(strict_types=1);

namespace App\Cash\Domain\ValueObject;

final class DeviceId
{
    private const MAX_LENGTH = 100;

    private readonly string $value;

    private function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('Device id cannot be empty.');
        }

        if (strlen($trimmed) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Device id cannot exceed %d characters.', self::MAX_LENGTH),
            );
        }

        $this->value = $trimmed;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
