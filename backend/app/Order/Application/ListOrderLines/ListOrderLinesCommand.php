<?php

namespace App\Order\Application\ListOrderLines;

final readonly class ListOrderLinesCommand
{
    public function __construct(
        public string $orderId,
    ) {}
}
