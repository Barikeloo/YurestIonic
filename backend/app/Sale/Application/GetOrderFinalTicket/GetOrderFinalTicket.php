<?php

declare(strict_types=1);

namespace App\Sale\Application\GetOrderFinalTicket;

use App\Sale\Domain\Exception\OrderFinalTicketNotFoundException;
use App\Sale\Domain\Interfaces\OrderFinalTicketRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetOrderFinalTicket
{
    public function __construct(
        private readonly OrderFinalTicketRepositoryInterface $orderFinalTicketRepository,
    ) {}

    public function __invoke(GetOrderFinalTicketCommand $command): GetOrderFinalTicketResponse
    {
        $ticket = $this->orderFinalTicketRepository->findByOrderId(Uuid::create($command->orderId))
            ?? throw OrderFinalTicketNotFoundException::withOrderId($command->orderId);

        return GetOrderFinalTicketResponse::fromEntity($ticket);
    }
}
