<?php

namespace App\Order\Application\TransferOrder;

final readonly class TransferOrderCommand
{
    public function __construct(
        public string $orderId,
        public string $toTableId,
        public string $transferredByUserId,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
