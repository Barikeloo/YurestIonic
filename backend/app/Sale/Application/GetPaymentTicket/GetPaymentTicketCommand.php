<?php

declare(strict_types=1);

namespace App\Sale\Application\GetPaymentTicket;

final readonly class GetPaymentTicketCommand
{
    public function __construct(
        public string $saleId,
    ) {}
}
