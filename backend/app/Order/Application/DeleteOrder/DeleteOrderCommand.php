<?php

namespace App\Order\Application\DeleteOrder;

final readonly class DeleteOrderCommand
{
    public function __construct(
        public string $id,
    ) {}
}
