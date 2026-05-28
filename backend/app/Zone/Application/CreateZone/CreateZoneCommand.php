<?php

namespace App\Zone\Application\CreateZone;

final readonly class CreateZoneCommand
{
    public function __construct(
        public string $name,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
