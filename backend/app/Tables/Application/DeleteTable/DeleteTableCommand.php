<?php

namespace App\Tables\Application\DeleteTable;

final readonly class DeleteTableCommand
{
    public function __construct(
        public string $id,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
