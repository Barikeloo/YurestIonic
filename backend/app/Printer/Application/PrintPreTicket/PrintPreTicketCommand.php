<?php

declare(strict_types=1);

namespace App\Printer\Application\PrintPreTicket;

final readonly class PrintPreTicketCommand
{
    public function __construct(
        public string $orderId,
    ) {}
}
