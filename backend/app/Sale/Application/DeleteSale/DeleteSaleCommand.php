<?php

declare(strict_types=1);

namespace App\Sale\Application\DeleteSale;

final readonly class DeleteSaleCommand
{
    public function __construct(
        public string $id,
    ) {}
}
