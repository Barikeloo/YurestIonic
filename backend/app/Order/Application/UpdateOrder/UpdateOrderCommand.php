<?php

namespace App\Order\Application\UpdateOrder;

final readonly class UpdateOrderCommand
{
    public function __construct(
        public string $id,
        public ?int $diners,
        public ?string $action,
        public ?string $closedByUserId,
    ) {}
}
