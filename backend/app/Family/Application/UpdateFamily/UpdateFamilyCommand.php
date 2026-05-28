<?php

namespace App\Family\Application\UpdateFamily;

final readonly class UpdateFamilyCommand
{
    public function __construct(
        public string $id,
        public string $name,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
