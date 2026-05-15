<?php

namespace App\ProductVariant\Application\CreateProductVariant;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductVariant\Domain\Entity\ProductVariant;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\ProductVariant\Domain\ValueObject\VariantName;
use App\ProductVariant\Domain\ValueObject\VariantPrice;
use App\ProductVariant\Domain\ValueObject\VariantStock;
use App\Shared\Domain\ValueObject\Uuid;

class CreateProductVariant
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductVariantRepositoryInterface $variantRepository,
    ) {}

    public function __invoke(CreateProductVariantCommand $command): CreateProductVariantResponse
    {
        $product = $this->productRepository->findById($command->productId)
            ?? throw ProductNotFoundException::withId($command->productId);

        $variant = ProductVariant::dddCreate(
            productId: Uuid::create($command->productId),
            name: VariantName::create($command->name),
            price: VariantPrice::create($command->price),
            stock: VariantStock::create($command->stock),
            active: $command->active,
            sortOrder: $command->sortOrder,
        );

        $this->variantRepository->save($variant);

        return CreateProductVariantResponse::create(
            id: $variant->id()->value(),
            productId: $variant->productId()->value(),
            name: $variant->name()->value(),
            price: $variant->price()->value(),
            stock: $variant->stock()->value(),
            active: $variant->isActive(),
            sortOrder: $variant->sortOrder(),
            createdAt: $variant->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $variant->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
