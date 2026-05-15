<?php

namespace App\ProductVariant\Application\UpdateProductVariant;

final readonly class UpdateProductVariantResponse
{
    private function __construct(
        public string $id,
        public string $productId,
        public string $name,
        public int $price,
        public int $stock,
        public bool $active,
        public int $sortOrder,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $productId,
        string $name,
        int $price,
        int $stock,
        bool $active,
        int $sortOrder,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            id: $id,
            productId: $productId,
            name: $name,
            price: $price,
            stock: $stock,
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
            'price' => $this->price,
            'stock' => $this->stock,
            'active' => $this->active,
            'sort_order' => $this->sortOrder,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
