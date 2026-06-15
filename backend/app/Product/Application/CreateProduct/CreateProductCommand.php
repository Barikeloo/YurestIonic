<?php

namespace App\Product\Application\CreateProduct;

final readonly class CreateProductCommand
{
    public function __construct(
        public string $familyId,
        public string $taxId,
        public ?string $imageSrc,
        public string $name,
        public int $price,
        public int $stock,
        public bool $active,
        public array $allergens = [],
    ) {}
}
