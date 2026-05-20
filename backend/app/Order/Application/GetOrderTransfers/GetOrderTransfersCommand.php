<?php

namespace App\Order\Application\GetOrderTransfers;

final readonly class GetOrderTransfersCommand
{
    public function __construct(
        public string $orderId,
    ) {}
}
