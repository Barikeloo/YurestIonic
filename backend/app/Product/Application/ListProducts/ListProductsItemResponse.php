<?php

namespace App\Product\Application\ListProducts;

use App\Product\Domain\Entity\Product;

final readonly class ListProductsItemResponse
{
    public function __construct(
        public string $id,
        public string $familyId,
        public string $taxId,
        public ?string $imageSrc,
        public string $name,
        public int $price,
        public int $stock,
        public bool $active,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(Product $product): self
    {
        return new self(
            id: $product->id()->value(),
            familyId: $product->familyId(),
            taxId: $product->taxId(),
            imageSrc: $product->imageSrc(),
            name: $product->name(),
            price: $product->price(),
            stock: $product->stock(),
            active: $product->isActive(),
            createdAt: $product->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $product->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, bool|int|string|null>
     */
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
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
