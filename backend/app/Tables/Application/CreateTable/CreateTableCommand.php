<?php

namespace App\Tables\Application\CreateTable;

final readonly class CreateTableCommand
{
    public function __construct(
        public string $zoneId,
        public string $name,
    ) {}
}
