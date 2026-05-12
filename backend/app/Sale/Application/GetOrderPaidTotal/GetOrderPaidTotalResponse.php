<?php

declare(strict_types=1);

namespace App\Sale\Application\GetOrderPaidTotal;

final readonly class GetOrderPaidTotalResponse
{
    private function __construct(
        public int $totalCents,
    ) {}

    public static function create(int $totalCents): self
    {
        return new self(totalCents: $totalCents);
    }

    public function toArray(): array
    {
        return [
            'total_cents' => $this->totalCents,
        ];
    }
}
