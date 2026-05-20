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

    /**
     * Devuelve la comanda en estado `open` o `to-charge` que ocupa la mesa,
     * o null si la mesa está libre. Útil para validar destinos de traspaso.
     */
    public function findActiveByTableId(Uuid $tableId): ?Order;

    public function countActiveByRestaurantId(Uuid $restaurantId): int;

    public function delete(Uuid $id): void;
}
