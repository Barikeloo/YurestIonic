<?php

namespace App\Order\Domain\ValueObject;

final class OrderLineDiscountAmount
{
    private function __construct(
        private int $cents,
    ) {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Order line discount amount cannot be negative.');
        }
    }

    public static function create(int $cents): self
    {
        return new self($cents);
    }

    public function value(): int
    {
        return $this->cents;
    }
}
