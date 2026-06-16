<?php

namespace App\Tables\Application\UnmergeTables;

final readonly class UnmergeTablesCommand
{
    public function __construct(
        public string $groupId,
        public string $restaurantId,
    ) {}
}
