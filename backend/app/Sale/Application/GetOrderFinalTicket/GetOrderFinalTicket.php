<?php

declare(strict_types=1);

namespace App\Sale\Application\GetOrderFinalTicket;

use App\Sale\Domain\Interfaces\OrderFinalTicketRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetOrderFinalTicket
{
    public function __construct(
        private readonly OrderFinalTicketRepositoryInterface $orderFinalTicketRepository,
    ) {}

    public function __invoke(string $orderId): ?GetOrderFinalTicketResponse
    {
        $ticket = $this->orderFinalTicketRepository->findByOrderId(Uuid::create($orderId));

        if ($ticket === null) {
            return null;
        }

        return GetOrderFinalTicketResponse::fromEntity($ticket);
    }
}
