<?php

namespace App\Product\Application\CreateProduct;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductAllergens;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

class CreateProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private EventBusInterface $eventBus,
    ) {}

    public function __invoke(CreateProductCommand $command): CreateProductResponse
    {
        $product = Product::dddCreate(
            familyId: Uuid::create($command->familyId),
            taxId: Uuid::create($command->taxId),
            imageSrc: ProductImageSrc::create($command->imageSrc),
            name: ProductName::create($command->name),
            price: ProductPrice::create($command->price),
            stock: ProductStock::create($command->stock),
            active: $command->active,
            allergens: ProductAllergens::create($command->allergens),
        );

        $this->productRepository->save($product);
        $this->eventBus->publish(...$product->pullDomainEvents());

        return CreateProductResponse::create(
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
        );
    }
}
