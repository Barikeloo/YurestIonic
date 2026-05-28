<?php

namespace App\Tax\Application\UpdateTax;

final readonly class UpdateTaxCommand
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public ?string $name = null,
        public ?int $percentage = null,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
