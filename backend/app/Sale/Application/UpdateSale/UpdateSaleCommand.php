<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateSale;

final readonly class UpdateSaleCommand
{
    public function __construct(
        public string $id,
        public string $closedByUserId,
        public int $ticketNumber,
    ) {}
}
