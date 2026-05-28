<?php

namespace App\Tax\Application\CreateTax;

final readonly class CreateTaxCommand
{
    public function __construct(
        public string $name,
        public int $percentage,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
