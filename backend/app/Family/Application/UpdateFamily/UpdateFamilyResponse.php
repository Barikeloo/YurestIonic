<?php

namespace App\Family\Application\UpdateFamily;

final readonly class UpdateFamilyResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public ?string $color,
        public ?string $icon,
        public bool $active,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $name,
        ?string $color,
        ?string $icon,
        bool $active,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            color: $color,
            icon: $icon,
            active: $active,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'icon' => $this->icon,
            'active' => $this->active,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
