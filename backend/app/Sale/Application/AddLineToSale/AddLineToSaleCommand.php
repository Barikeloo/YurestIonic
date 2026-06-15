<?php

declare(strict_types=1);

namespace App\Sale\Application\AddLineToSale;

final readonly class AddLineToSaleCommand
{
    public function __construct(
        public string $restaurantId,
        public string $saleId,
        public string $orderLineId,
        public string $userId,
        public int $quantity,
        public int $price,
        public int $taxPercentage,
    ) {}
}
