<?php

namespace App\Product\Domain\ValueObject;

class ProductImageSrc
{
    private const MAX_LENGTH = 255;

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
                sprintf('Product image source cannot exceed %d characters.', self::MAX_LENGTH)
            );
        }

        $this->value = $trimmed;
    }

    public static function create(?string $value): self
    {
        return new self($value);
    }

    public function value(): ?string
    {
        return $this->value;
    }
}
