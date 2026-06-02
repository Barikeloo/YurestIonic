<?php

namespace App\ProductModifier\Application\UpdateProductModifier;

final readonly class UpdateProductModifierCommand
{
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public bool $isRequired,
        public string $selectionType,
        public int $price,
        public bool $active,
        public int $sortOrder,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
