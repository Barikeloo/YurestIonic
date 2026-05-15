<?php

declare(strict_types=1);

namespace App\Product\Application\ListProducts;

use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;

class ListProducts
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductVariantRepositoryInterface $variantRepository,
        private ProductModifierRepositoryInterface $modifierRepository,
    ) {}

    public function __invoke(ListProductsCommand $command): ListProductsResponse
    {
        $products = $this->productRepository->findAll($command->includeDeleted);

        if ($command->onlyActive) {
            $products = array_filter($products, fn ($p) => $p->isActive());
        }

        $items = array_values(array_map(
            fn ($product): ListProductsItemResponse => ListProductsItemResponse::create(
                id: $product->id()->value(),
                familyId: $product->familyId()->value(),
                taxId: $product->taxId()->value(),
                imageSrc: $product->imageSrc()->value(),
                name: $product->name()->value(),
                price: $product->price()->value(),
                stock: $product->stock()->value(),
                active: $product->isActive(),
                allergens: $product->allergens()->values(),
                createdAt: $product->createdAt()->format(\DateTimeInterface::ATOM),
                updatedAt: $product->updatedAt()->format(\DateTimeInterface::ATOM),
                variants: $this->mapVariants($product->id()->value()),
                modifiers: $this->mapModifiers($product->id()->value()),
            ),
            $products,
        ));

        return ListProductsResponse::create($items);
    }

    private function mapVariants(string $productId): array
    {
        return array_map(
            static fn ($variant): array => [
                'id' => $variant->id()->value(),
                'name' => $variant->name()->value(),
                'price' => $variant->price()->value(),
                'stock' => $variant->stock()->value(),
                'active' => $variant->isActive(),
            ],
            $this->variantRepository->findByProductId($productId),
        );
    }

    private function mapModifiers(string $productId): array
    {
        return array_map(
            static fn ($modifier): array => [
                'id' => $modifier->id()->value(),
                'name' => $modifier->name()->value(),
                'type' => $modifier->type()->value(),
                'is_required' => $modifier->isRequired(),
                'selection_type' => $modifier->selectionType()->value(),
                'price' => $modifier->price()->value(),
                'active' => $modifier->isActive(),
            ],
            $this->modifierRepository->findByProductId($productId),
        );
    }
}
