<?php

namespace App\Tables\Application\ListTables;

final readonly class ListTablesItemResponse
{
    private function __construct(
        public string  $id,
        public string  $zoneId,
        public string  $name,
        public string  $createdAt,
        public string  $updatedAt,
        public ?string $mergedTableGroupId,
        public ?array  $layout,
    ) {}

    public static function create(
        string  $id,
        string  $zoneId,
        string  $name,
        string  $createdAt,
        string  $updatedAt,
        ?string $mergedTableGroupId = null,
        ?array  $layout = null,
    ): self {
        return new self(
            id:                 $id,
            zoneId:             $zoneId,
            name:               $name,
            createdAt:          $createdAt,
            updatedAt:          $updatedAt,
            mergedTableGroupId: $mergedTableGroupId,
            layout:             $layout,
        );
    }

    public function toArray(): array
    {
        return [
            'id'                     => $this->id,
            'zone_id'                => $this->zoneId,
            'name'                   => $this->name,
            'created_at'             => $this->createdAt,
            'updated_at'             => $this->updatedAt,
            'merged_table_group_id'  => $this->mergedTableGroupId,
            'layout'                 => $this->layout,
        ];
    }
}
