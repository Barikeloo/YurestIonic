<?php

namespace App\ProductVariant\Application\DeleteProductVariant;

final readonly class DeleteProductVariantCommand
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
