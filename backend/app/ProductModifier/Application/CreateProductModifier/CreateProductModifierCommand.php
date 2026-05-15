<?php

namespace App\ProductModifier\Application\CreateProductModifier;

final readonly class CreateProductModifierCommand
{
    public function __construct(
        public string $productId,
        public string $name,
        public string $type,
        public bool $isRequired,
        public string $selectionType,
        public int $price,
        public bool $active,
        public int $sortOrder,
    ) {}
}
