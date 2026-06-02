<?php

namespace App\Order\Application\DeleteOrder;

final readonly class DeleteOrderCommand
{
    public function __construct(
        public string $id,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
