<?php

namespace App\Order\Domain\Interfaces;

use App\Order\Domain\Entity\OrderTransfer;
use App\Shared\Domain\ValueObject\Uuid;

interface OrderTransferRepositoryInterface
{
    public function save(OrderTransfer $transfer): void;

    /**
     * @return OrderTransfer[]
     */
    public function findByOrderId(Uuid $orderId): array;
}
