<?php

namespace App\Tables\Application\CreateTable;

final readonly class CreateTableCommand
{
    public function __construct(
        public string $zoneId,
        public string $name,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
