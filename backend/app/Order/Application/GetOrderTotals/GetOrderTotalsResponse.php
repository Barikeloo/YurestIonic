<?php

declare(strict_types=1);

namespace App\Order\Application\GetOrderTotals;

final class GetOrderTotalsResponse
{
    private function __construct(
        private readonly int $subtotal,
        private readonly int $tax,
        private readonly int $total,
    ) {}

    public static function create(int $subtotal, int $tax, int $total): self
    {
        return new self($subtotal, $tax, $total);
    }

    public function subtotal(): int
    {
        return $this->subtotal;
    }

    public function tax(): int
    {
        return $this->tax;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function toArray(): array
    {
        return [
            'subtotal_cents' => $this->subtotal,
            'tax_cents' => $this->tax,
            'total_cents' => $this->total,
        ];
    }
}
