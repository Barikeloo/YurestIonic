<?php

namespace App\Order\Application\TransferOrder;

final readonly class TransferOrderResponse
{
    private function __construct(
        public string $transferId,
        public string $orderId,
        public string $fromTableId,
        public string $toTableId,
        public string $transferredAt,
    ) {}

    public static function create(
        string $transferId,
        string $orderId,
        string $fromTableId,
        string $toTableId,
        string $transferredAt,
    ): self {
        return new self(
            transferId: $transferId,
            orderId: $orderId,
            fromTableId: $fromTableId,
            toTableId: $toTableId,
            transferredAt: $transferredAt,
        );
    }

    public function toArray(): array
    {
        return [
            'transfer_id' => $this->transferId,
            'order_id' => $this->orderId,
            'from_table_id' => $this->fromTableId,
            'to_table_id' => $this->toTableId,
            'transferred_at' => $this->transferredAt,
        ];
    }
}
