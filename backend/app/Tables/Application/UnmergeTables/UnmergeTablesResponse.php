<?php

namespace App\Tables\Application\UnmergeTables;

final readonly class UnmergeTablesResponse
{
    private function __construct(
        public array $unmergedTableIds,
    ) {}

    public static function create(array $unmergedTableIds): self
    {
        return new self(
            unmergedTableIds: $unmergedTableIds,
        );
    }

    public function toArray(): array
    {
        return [
            'unmerged_table_ids' => $this->unmergedTableIds,
        ];
    }
}
