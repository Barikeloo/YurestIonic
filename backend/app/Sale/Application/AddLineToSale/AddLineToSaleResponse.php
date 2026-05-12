<?php

declare(strict_types=1);

namespace App\Sale\Application\AddLineToSale;

use App\Sale\Domain\Entity\SaleLine;

final readonly class AddLineToSaleResponse
{
    private function __construct(
        public string $id,
        public string $uuid,
        public string $saleId,
        public string $orderLineId,
        public string $productId,
        public int $quantity,
        public int $price,
        public int $taxPercentage,
    ) {}

    public static function create(
        string $id,
        string $uuid,
        string $saleId,
        string $orderLineId,
        string $productId,
        int $quantity,
        int $price,
        int $taxPercentage,
    ): self {
        return new self(
            id: $id,
            uuid: $uuid,
            saleId: $saleId,
            orderLineId: $orderLineId,
            productId: $productId,
            quantity: $quantity,
            price: $price,
            taxPercentage: $taxPercentage,
        );
    }

    public static function fromSaleLine(SaleLine $saleLine): self
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
