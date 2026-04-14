<?php

namespace App\Order\Application\AddLineToOrder;

use App\Order\Domain\Entity\OrderLine;

final class AddLineToOrderResponse
{
    private function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $orderId,
        public readonly string $productId,
        public readonly int $quantity,
        public readonly int $price,
        public readonly int $taxPercentage,
    ) {}

    public static function create(OrderLine $orderLine): self
    {
        return new self(
            id: $orderLine->getId()->value(),
            uuid: $orderLine->getUuid()->value(),
            orderId: $orderLine->getOrderId()->value(),
            productId: $orderLine->getProductId()->value(),
            quantity: $orderLine->getQuantity()->value(),
            price: $orderLine->getPrice()->value(),
            taxPercentage: $orderLine->getTaxPercentage()->value(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'tax_percentage' => $this->taxPercentage,
        ];
    }
}
