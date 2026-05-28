<?php

namespace App\Order\Application\DeleteOrderLine;

final readonly class DeleteOrderLineCommand
{
    public function __construct(
        public string $lineId,
        public string $userId,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
