<?php

namespace App\SuperAdmin\Domain\ValueObject;

final class SuperAdminPasswordHash
{
    private const MIN_LENGTH = 60;
    private const MAX_LENGTH = 255;

    private function __construct(
        private string $value,
    ) {
        $length = strlen($value);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Superadmin password hash must be between %d and %d characters.', self::MIN_LENGTH, self::MAX_LENGTH)
            );
        }
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