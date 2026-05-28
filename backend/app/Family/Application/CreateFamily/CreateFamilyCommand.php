<?php

namespace App\Family\Application\CreateFamily;

final readonly class CreateFamilyCommand
{
    public function __construct(
        public string $name,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
