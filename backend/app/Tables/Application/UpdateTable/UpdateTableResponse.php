<?php

namespace App\Tables\Application\UpdateTable;

use App\Tables\Domain\Entity\Table;

final readonly class UpdateTableResponse
{
    public function __construct(
        public string $id,
        public string $zoneId,
        public string $name,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(Table $table): self
    {
        return new self(
            id: $table->id()->value(),
            zoneId: $table->zoneId(),
            name: $table->name(),
            createdAt: $table->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $table->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, string>
     */
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
