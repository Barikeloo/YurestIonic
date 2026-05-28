<?php

namespace App\Product\Application\CreateProduct;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductAllergens;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\Uuid;

class CreateProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private readonly AuditRecorderInterface $auditRecorder,
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

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('product.created'),
            entityType: 'product',
            entityId: $product->id()->value(),
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'product_name' => $product->name()->value(),
                'price_cents' => $product->price()->value(),
                'price_formatted' => number_format($product->price()->value() / 100, 2).' €',
                'family_id' => $product->familyId()->value(),
                'active' => $product->isActive(),
            ],
        ));

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
