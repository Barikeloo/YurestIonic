<?php

namespace App\Product\Application\SetProductActive;

final readonly class SetProductActiveCommand
{
    public function __construct(
        public string $id,
        public bool $active,
        public string $restaurantId,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
