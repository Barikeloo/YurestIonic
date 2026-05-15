<?php

namespace App\Order\Application\GetOrder;

final readonly class GetOrderCommand
{
    public function __construct(
        public string $id,
    ) {}
}
