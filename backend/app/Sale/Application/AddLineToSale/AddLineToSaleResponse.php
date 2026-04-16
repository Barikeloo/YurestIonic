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
            id: $saleLine->id()->value(),
            uuid: $saleLine->uuid()->value(),
            saleId: $saleLine->saleId()->value(),
            orderLineId: $saleLine->orderLineId()->value(),
            productId: $saleLine->productId()->value(),
            quantity: $saleLine->quantity()->value(),
            price: $saleLine->price()->value(),
            taxPercentage: $saleLine->taxPercentage()->value(),
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
