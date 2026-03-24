<?php

namespace App\Tax\Application\CreateTax;

use App\Tax\Domain\Entity\Tax;

final readonly class CreateTaxResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public int $percentage,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(Tax $tax): self
    {
        return new self(
            id: $tax->id()->value(),
            name: $tax->name(),
            percentage: $tax->percentage(),
            createdAt: $tax->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $tax->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, int|string>
     */
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
