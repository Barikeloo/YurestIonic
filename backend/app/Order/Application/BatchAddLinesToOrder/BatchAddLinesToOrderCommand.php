<?php

declare(strict_types=1);

namespace App\Order\Application\BatchAddLinesToOrder;

final readonly class BatchAddLinesToOrderCommand
{

    public function __construct(
        public string $restaurantId,
        public string $orderId,
        public string $userId,
        public array $productLines,
        public array $menuLines,
    ) {}
}
