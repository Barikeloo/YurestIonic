<?php

namespace App\Zone\Application\GetZone;

use App\Zone\Domain\Entity\Zone;

final readonly class GetZoneResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(Zone $zone): self
    {
        return new self(
            id: $zone->id()->value(),
            name: $zone->name(),
            createdAt: $zone->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $zone->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, string>
     */
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
