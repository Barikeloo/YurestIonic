<?php

namespace App\Family\Application\ListFamilies;

use App\Family\Domain\Entity\Family;

final readonly class ListFamiliesItemResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $active,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(Family $family): self
    {
        return new self(
            id: $family->id()->value(),
            name: $family->name(),
            active: $family->isActive(),
            createdAt: $family->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $family->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, bool|string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'active' => $this->active,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
