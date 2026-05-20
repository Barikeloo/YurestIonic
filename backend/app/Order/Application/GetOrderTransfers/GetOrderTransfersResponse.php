<?php

namespace App\Order\Application\GetOrderTransfers;

final readonly class GetOrderTransfersResponse
{
    private function __construct(
        public array $transfers,
    ) {}

    /**
     * @param array<int, array{
     *     id: string,
     *     order_id: string,
     *     from_table_id: string,
     *     to_table_id: string,
     *     transferred_by_user_id: string,
     *     transferred_at: string
     * }> $transfers
     */
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
