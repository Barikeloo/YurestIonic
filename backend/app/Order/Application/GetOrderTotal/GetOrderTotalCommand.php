<?php

namespace App\Order\Application\GetOrderTotal;

final readonly class GetOrderTotalCommand
{
    public function __construct(
        public string $orderId,
    ) {}
}
