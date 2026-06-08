<?php

namespace App\Tables\Application\ListTables;

final readonly class ListTablesResponse
{

    private function __construct(
        public array $items,
    ) {}

    public static function create(array $items): self
    {
        return new self(
            items: $items,
        );
    }

    public function toArray(): array
    {
        return $this->items;
    }
}
