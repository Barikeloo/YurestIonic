<?php

namespace App\Tables\Application\MergeTables;

final readonly class MergeTablesCommand
{
    public function __construct(
        public array $tableIds,
    ) {}
}
