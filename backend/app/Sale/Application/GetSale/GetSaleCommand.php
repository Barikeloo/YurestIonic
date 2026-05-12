<?php

declare(strict_types=1);

namespace App\Sale\Application\GetSale;

final readonly class GetSaleCommand
{
    public function __construct(
        public string $id,
    ) {}
}
