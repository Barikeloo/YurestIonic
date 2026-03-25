<?php

namespace App\Sale\Application\AddLineToSale;

use App\Sale\Domain\Entity\SaleLine;

final class AddLineToSaleResponse
{
    private function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $saleId,
        public readonly string $orderLineId,
        public readonly string $productId,
        public readonly int $quantity,
        public readonly int $price,
        public readonly int $taxPercentage,
    ) {}

    public static function create(SaleLine $saleLine): self
    {
        return new self(
            id: $saleLine->getId()->value(),
            uuid: $saleLine->getUuid()->value(),
            saleId: $saleLine->getSaleId()->value(),
            orderLineId: $saleLine->getOrderLineId()->value(),
            productId: $saleLine->getProductId()->value(),
            quantity: $saleLine->getQuantity(),
            price: $saleLine->getPrice(),
            taxPercentage: $saleLine->getTaxPercentage(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'sale_id' => $this->saleId,
            'order_line_id' => $this->orderLineId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'tax_percentage' => $this->taxPercentage,
        ];
    }
}
