<?php

namespace App\ProductModifier\Application\CreateProductModifier;

final readonly class CreateProductModifierResponse
{
    private function __construct(
        public string $id,
        public string $productId,
        public string $name,
        public string $type,
        public bool $isRequired,
        public string $selectionType,
        public int $price,
        public bool $active,
        public int $sortOrder,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $productId,
        string $name,
        string $type,
        bool $isRequired,
        string $selectionType,
        int $price,
        bool $active,
        int $sortOrder,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            id: $id,
            productId: $productId,
            name: $name,
            type: $type,
            isRequired: $isRequired,
            selectionType: $selectionType,
            price: $price,
            active: $active,
            sortOrder: $sortOrder,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'name' => $this->name,
            'type' => $this->type,
            'is_required' => $this->isRequired,
            'selection_type' => $this->selectionType,
            'price' => $this->price,
            'active' => $this->active,
            'sort_order' => $this->sortOrder,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
