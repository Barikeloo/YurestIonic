<?php

declare(strict_types=1);

namespace App\Sale\Application\CancelSale;

final readonly class CancelSaleCommand
{
    public function __construct(
        public string $saleId,
        public string $cancelledByUserId,
        public string $reason,
    ) {}
}
