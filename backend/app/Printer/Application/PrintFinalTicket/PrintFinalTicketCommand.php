<?php

declare(strict_types=1);

namespace App\Printer\Application\PrintFinalTicket;

final readonly class PrintFinalTicketCommand
{
    public function __construct(
        public string $orderId,
    ) {}
}
