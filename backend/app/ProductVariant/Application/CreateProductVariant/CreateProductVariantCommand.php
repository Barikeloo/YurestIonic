<?php

namespace App\ProductVariant\Application\CreateProductVariant;

final readonly class CreateProductVariantCommand
{
    public function __construct(
        public string $productId,
        public string $name,
        public int $price,
        public int $stock,
        public bool $active,
        public int $sortOrder,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
