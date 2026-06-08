<?php

namespace App\Tax\Application\CreateTax;

final readonly class CreateTaxResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public int $percentage,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $name,
        int $percentage,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            percentage: $percentage,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'percentage' => $this->percentage,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
