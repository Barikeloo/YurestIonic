<?php

namespace App\ProductVariant\Application\ListProductVariants;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductVariant\Domain\Entity\ProductVariant;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;

class ListProductVariants
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductVariantRepositoryInterface $variantRepository,
    ) {}

    public function __invoke(ListProductVariantsCommand $command): ListProductVariantsResponse
    {
        $product = $this->productRepository->findById($command->productId)
            ?? throw ProductNotFoundException::withId($command->productId);

        $variants = $this->variantRepository->findByProductId($command->productId);

        return ListProductVariantsResponse::create(
            variants: array_map(
                static fn (ProductVariant $variant): array => [
                    'id' => $variant->id()->value(),
                    'product_id' => $variant->productId()->value(),
                    'name' => $variant->name()->value(),
                    'price' => $variant->price()->value(),
                    'stock' => $variant->stock()->value(),
                    'active' => $variant->isActive(),
                    'sort_order' => $variant->sortOrder(),
                    'created_at' => $variant->createdAt()->format(\DateTimeInterface::ATOM),
                    'updated_at' => $variant->updatedAt()->format(\DateTimeInterface::ATOM),
                ],
                $variants,
            ),
        );
    }
}
