<?php

declare(strict_types=1);

namespace App\Menu\Domain\ValueObject;

final class MenuDescription
{
    private const MAX_LENGTH = 2000;

    private ?string $value;

    private function __construct(?string $value)
    {
        if ($value === null) {
            $this->value = null;

            return;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            $this->value = null;

            return;
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Menu description cannot exceed %d characters.', self::MAX_LENGTH)
            );
        }

        $this->value = $trimmed;
    }

    public static function create(?string $value): self
    {
        return new self($value);
    }

    public static function empty(): self
    {
        return new self(null);
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return $this->value === null;
    }
}
