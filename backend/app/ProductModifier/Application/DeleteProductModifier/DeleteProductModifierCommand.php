<?php

namespace App\ProductModifier\Application\DeleteProductModifier;

final readonly class DeleteProductModifierCommand
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
