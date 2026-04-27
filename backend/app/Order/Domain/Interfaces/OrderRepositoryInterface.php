<?php

namespace App\Order\Domain\Interfaces;

use App\Order\Domain\Entity\Order;
use App\Shared\Domain\ValueObject\Uuid;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function all(): array;

    public function findByUuid(Uuid $uuid): ?Order;

    public function findByTableId(Uuid $tableId): ?Order;

    public function delete(Uuid $id): void;
}
