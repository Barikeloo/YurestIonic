<?php

namespace App\Tables\Application\ListTables;

final readonly class ListTablesCommand
{
    public function __construct(
        public ?bool $includeDeleted,
        public ?string $zoneId = null,
    ) {}
}
