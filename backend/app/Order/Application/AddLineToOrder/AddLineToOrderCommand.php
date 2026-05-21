<?php

namespace App\Order\Application\AddLineToOrder;

final readonly class AddLineToOrderCommand
{
    /**
     * @param  array<int, array{id: string, name: string, price: int, type: string}>|null  $modifiers
     */
    public function __construct(
        public string $restaurantId,
        public string $orderId,
        public string $productId,
        public string $userId,
        public int $quantity,
        public ?int $dinerNumber,
        public ?string $variantId = null,
        public ?array $modifiers = null,
    ) {}
}
