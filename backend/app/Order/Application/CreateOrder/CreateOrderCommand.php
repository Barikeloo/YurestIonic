<?php

namespace App\Order\Application\CreateOrder;

final readonly class CreateOrderCommand
{
    public function __construct(
        public string $restaurantId,
        public string $tableId,
        public string $openedByUserId,
        public int $diners,
    ) {}
}
