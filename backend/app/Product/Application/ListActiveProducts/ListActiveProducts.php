<?php

declare(strict_types=1);

namespace App\Product\Application\ListActiveProducts;

use App\Product\Application\ListProducts\ListProducts;

final class ListActiveProducts
{
    public function __construct(
        private ListProducts $listProducts,
    ) {}

    public function __invoke(): array
    {
        return ($this->listProducts)(includeDeleted: false, onlyActive: true);
    }
}
