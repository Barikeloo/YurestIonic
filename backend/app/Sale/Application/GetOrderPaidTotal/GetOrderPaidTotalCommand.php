<?php

declare(strict_types=1);

namespace App\Sale\Application\GetOrderPaidTotal;

final readonly class GetOrderPaidTotalCommand
{
    public function __construct(
        public string $orderId,
    ) {}
}
