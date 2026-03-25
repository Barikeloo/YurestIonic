<?php

namespace App\Order\Application\ListOrderLines;

use App\Order\Domain\Entity\OrderLine;

final class ListOrderLinesResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $restaurant_id,
        public readonly string $order_id,
        public readonly string $product_id,
        public readonly ?string $product_name,
        public readonly string $user_id,
        public readonly int $quantity,
        public readonly int $price,
        public readonly int $tax_percentage,
        public readonly string $created_at,
        public readonly string $updated_at,
    ) {}

    public static function create(OrderLine $orderLine, ?string $productName = null): self
    {
        return new self(
            id: $orderLine->getId()->value(),
            uuid: $orderLine->getUuid()->value(),
            restaurant_id: $orderLine->getRestaurantId()->value(),
            order_id: $orderLine->getOrderId()->value(),
            product_id: $orderLine->getProductId()->value(),
            product_name: $productName,
            user_id: $orderLine->getUserId()->value(),
            quantity: $orderLine->getQuantity(),
            price: $orderLine->getPrice(),
            tax_percentage: $orderLine->getTaxPercentage(),
            created_at: $orderLine->getCreatedAt()->format('Y-m-d H:i:s'),
            updated_at: $orderLine->getUpdatedAt()->format('Y-m-d H:i:s'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'restaurant_id' => $this->restaurant_id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'user_id' => $this->user_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'tax_percentage' => $this->tax_percentage,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
