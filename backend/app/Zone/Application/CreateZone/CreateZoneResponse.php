<?php

namespace App\Zone\Application\CreateZone;

final readonly class CreateZoneResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $name,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
