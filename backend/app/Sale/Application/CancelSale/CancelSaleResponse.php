<?php

namespace App\Sale\Application\CancelSale;

use App\Sale\Domain\Entity\Sale;

final class CancelSaleResponse
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $orderId,
        public readonly string $status,
        public readonly int $totalCents,
        public readonly ?string $cancelledByUserId,
        public readonly ?string $cancellationReason,
    ) {}

    public static function create(Sale $sale): self
    {
        return new self(
            uuid: $sale->uuid()->value(),
            orderId: $sale->orderId()->value(),
            status: $sale->status(),
            totalCents: $sale->total()->value(),
            cancelledByUserId: $sale->cancelledByUserId()?->value(),
            cancellationReason: $sale->cancellationReason(),
        );
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'order_id' => $this->orderId,
            'status' => $this->status,
            'total_cents' => $this->totalCents,
            'cancelled_by_user_id' => $this->cancelledByUserId,
            'cancellation_reason' => $this->cancellationReason,
        ];
    }
}
