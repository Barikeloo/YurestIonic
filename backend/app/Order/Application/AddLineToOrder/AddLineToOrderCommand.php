<?php

namespace App\Order\Application\AddLineToOrder;

final readonly class AddLineToOrderCommand
{
    public function __construct(
        public string $restaurantId,
        public string $orderId,
        public string $productId,
        public string $userId,
        public int $quantity,
        public ?int $dinerNumber,
    ) {}
}
