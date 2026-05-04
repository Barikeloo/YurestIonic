<?php

declare(strict_types=1);

namespace App\Sale\Domain\Interfaces;

use App\Sale\Domain\Entity\OrderFinalTicket;
use App\Shared\Domain\ValueObject\Uuid;

interface OrderFinalTicketRepositoryInterface
{
    public function save(OrderFinalTicket $ticket): void;

    public function findByUuid(Uuid $uuid): ?OrderFinalTicket;

    public function findByOrderId(Uuid $orderId): ?OrderFinalTicket;

    public function nextTicketNumber(Uuid $restaurantId): int;
}
