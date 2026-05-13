<?php

namespace App\Tables\Application\MergeTables;

final readonly class MergeTablesResponse
{
    private function __construct(
        public string $groupId,
        public array $mergedTableIds,
    ) {}

    public static function create(string $groupId, array $mergedTableIds): self
    {
        return new self(
            groupId: $groupId,
            mergedTableIds: $mergedTableIds,
        );
    }

    public function toArray(): array
    {
        return [
            'group_id' => $this->groupId,
            'merged_table_ids' => $this->mergedTableIds,
        ];
    }
}
