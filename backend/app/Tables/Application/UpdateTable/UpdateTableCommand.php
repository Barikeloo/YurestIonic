<?php

namespace App\Tables\Application\UpdateTable;

final readonly class UpdateTableCommand
{
    public function __construct(
        public string $id,
        public string $zoneId,
        public string $name,
    ) {}
}
