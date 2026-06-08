<?php

namespace App\Order\Application\GetOrderTransfers;

final readonly class GetOrderTransfersResponse
{
    private function __construct(
        public array $transfers,
    ) {}

    public static function create(array $transfers): self
    {
        return new self(transfers: $transfers);
    }

    public function toArray(): array
    {
        return [
            'transfers' => $this->transfers,
        ];
    }
}
