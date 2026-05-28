<?php

namespace App\Tables\Application\UpdateTable;

final readonly class UpdateTableCommand
{
    public function __construct(
        public string $id,
        public string $zoneId,
        public string $name,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
