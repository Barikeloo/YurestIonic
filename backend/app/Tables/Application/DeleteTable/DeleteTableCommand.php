<?php

namespace App\Tables\Application\DeleteTable;

final readonly class DeleteTableCommand
{
    public function __construct(
        public string $id,
    ) {}
}
