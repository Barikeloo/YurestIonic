<?php

namespace App\Tables\Application\MergeTables;

final readonly class MergeTablesCommand
{
    /** @param list<string> $tableIds */
    public function __construct(
        public array $tableIds,
        public string $restaurantId,
    ) {}
}
