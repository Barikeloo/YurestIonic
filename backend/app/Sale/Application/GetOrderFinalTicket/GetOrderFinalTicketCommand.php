<?php

declare(strict_types=1);

namespace App\Sale\Application\GetOrderFinalTicket;

final readonly class GetOrderFinalTicketCommand
{
    public function __construct(
        public string $orderId,
    ) {}
}
