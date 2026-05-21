<?php

declare(strict_types=1);

namespace App\Menu\Domain\ValueObject;

final class MenuSectionName
{
    private const MAX_LENGTH = 255;

    private string $value;

    private function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('Menu section name cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Menu section name cannot exceed %d characters.', self::MAX_LENGTH)
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
