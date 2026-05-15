<?php

namespace App\Order\Application\DeleteOrderLine;

final readonly class DeleteOrderLineCommand
{
    public function __construct(
        public string $lineId,
    ) {}
}
