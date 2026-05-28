<?php

namespace App\Product\Application\UpdateProduct;

final readonly class UpdateProductCommand
{
    /**
     * @param  string[]  $allergens
     */
    public function __construct(
        public string $id,
        public string $familyId,
        public string $taxId,
        public ?string $imageSrc,
        public string $name,
        public int $price,
        public int $stock,
        public bool $active,
        public array $allergens,
        public string $restaurantId,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
