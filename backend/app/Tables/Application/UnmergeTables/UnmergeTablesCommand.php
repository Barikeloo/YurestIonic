<?php

namespace App\Tables\Application\UnmergeTables;

final readonly class UnmergeTablesCommand
{
    public function __construct(
        public string $groupId,
        public string $restaurantId,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
