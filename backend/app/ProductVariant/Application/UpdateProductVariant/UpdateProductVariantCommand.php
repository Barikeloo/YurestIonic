<?php

namespace App\ProductVariant\Application\UpdateProductVariant;

final readonly class UpdateProductVariantCommand
{
    public function __construct(
        public string $id,
        public string $name,
        public int $price,
        public int $stock,
        public bool $active,
        public int $sortOrder,
    ) {}
}
