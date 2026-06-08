<?php

namespace App\Product\Application\ListProducts;

final readonly class ListProductsItemResponse
{

    private function __construct(
        public string $id,
        public string $familyId,
        public string $taxId,
        public ?string $imageSrc,
        public string $name,
        public int $price,
        public int $stock,
        public bool $active,
        public array $allergens,
        public string $createdAt,
        public string $updatedAt,
        public array $variants = [],
        public array $modifiers = [],
    ) {}

    public static function create(
        string $id,
        string $familyId,
        string $taxId,
        ?string $imageSrc,
        string $name,
        int $price,
        int $stock,
        bool $active,
        array $allergens,
        string $createdAt,
        string $updatedAt,
        array $variants = [],
        array $modifiers = [],
    ): self {
        return new self(
            id: $id,
            familyId: $familyId,
            taxId: $taxId,
            imageSrc: $imageSrc,
            name: $name,
            price: $price,
            stock: $stock,
            active: $active,
            allergens: $allergens,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            variants: $variants,
            modifiers: $modifiers,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'family_id' => $this->familyId,
            'tax_id' => $this->taxId,
            'image_src' => $this->imageSrc,
            'name' => $this->name,
            'price' => $this->price,
            'stock' => $this->stock,
            'active' => $this->active,
            'allergens' => $this->allergens,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'variants' => $this->variants,
            'modifiers' => $this->modifiers,
        ];
    }
}
