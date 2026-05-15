<?php

namespace App\ProductVariant\Application\ListProductVariants;

final readonly class ListProductVariantsCommand
{
    public function __construct(
        public string $productId,
    ) {}
}
