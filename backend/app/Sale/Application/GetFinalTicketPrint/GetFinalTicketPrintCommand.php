<?php

declare(strict_types=1);

namespace App\Sale\Application\GetFinalTicketPrint;

final readonly class GetFinalTicketPrintCommand
{
    public function __construct(
        public string $orderId,
    ) {}
}
