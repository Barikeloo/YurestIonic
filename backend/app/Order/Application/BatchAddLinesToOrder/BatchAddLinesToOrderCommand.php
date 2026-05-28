<?php

declare(strict_types=1);

namespace App\Order\Application\BatchAddLinesToOrder;

final readonly class BatchAddLinesToOrderCommand
{
    /**
     * @param  list<array{product_id: string, quantity: int, variant_id: ?string, modifiers: ?list<array{id: string, name: string, price: int, type: string}>, diner_number: ?int}>  $productLines
     * @param  list<array{menu_id: string, notes: ?string, selections: list<array{section_id: string, product_id: string, variant_id: ?string, modifiers: list<array{id: string, name: string, price: int, type: string}>}>}>  $menuLines
     */
    public function __construct(
        public string $restaurantId,
        public string $orderId,
        public string $userId,
        public array $productLines,
        public array $menuLines,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
