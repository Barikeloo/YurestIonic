<?php

namespace App\Order\Application\GetOrderPreTicket;

final readonly class GetOrderPreTicketCommand
{
    public function __construct(
        public string $orderId,
        public string $format,
        public string $width,
    ) {}
}
