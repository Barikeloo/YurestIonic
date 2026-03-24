<?php

namespace App\Order\Domain\Interfaces;

use App\Order\Domain\Entity\OrderLine;
use App\Shared\Domain\ValueObject\Uuid;

interface OrderLineRepositoryInterface
{
    public function save(OrderLine $orderLine): void;

    public function findById(Uuid $id): ?OrderLine;

    public function findByUuid(Uuid $uuid): ?OrderLine;

    public function findByOrderId(Uuid $orderId): array;

    public function delete(Uuid $id): void;
}
