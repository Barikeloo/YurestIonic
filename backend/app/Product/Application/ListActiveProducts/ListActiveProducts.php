<?php

declare(strict_types=1);

namespace App\Product\Application\ListActiveProducts;

use App\Product\Application\ListProducts\ListProducts;

/**
 * List only active products for TPV use.
 * Delegates to ListProducts with onlyActive=true.
 */
final class ListActiveProducts
{
    public function __construct(
        private ListProducts $listProducts,
    ) {}

    /**
     * @return array<int, array<string, bool|int|string|null>>
     */
    public function __invoke(): array
    {
        return ($this->listProducts)(includeDeleted: false, onlyActive: true);
    }
}
