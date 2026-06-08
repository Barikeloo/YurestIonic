<?php

namespace App\Tables\Application\GetTable;

final readonly class GetTableResponse
{
    private function __construct(
        public string $id,
        public string $zoneId,
        public string $name,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $zoneId,
        string $name,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            id: $id,
            zoneId: $zoneId,
            name: $name,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'zone_id' => $this->zoneId,
            'name' => $this->name,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
