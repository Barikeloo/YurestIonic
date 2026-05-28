<?php

namespace App\Product\Application\UpdateProduct;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductAllergens;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\Uuid;

class UpdateProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(UpdateProductCommand $command): UpdateProductResponse
    {
        $product = $this->productRepository->findById($command->id)
            ?? throw ProductNotFoundException::withId($command->id);

        $priceBefore = $product->price()->value();

        $product->update(
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

        if ($priceBefore !== $command->price) {
            $this->auditRecorder->record(new AuditEventDraft(
                restaurantId: Uuid::create($command->restaurantId),
                slug: ActionSlug::create('product.price_changed'),
                entityType: 'product',
                entityId: $command->id,
                userId: null,
                deviceId: $command->deviceId,
                ipAddress: $command->ipAddress,
                metadata: [
                    'product_name' => $product->name()->value(),
                    'price_before_formatted' => number_format($priceBefore / 100, 2).' €',
                    'price_after_formatted' => number_format($command->price / 100, 2).' €',
                ],
            ));
        }

        return UpdateProductResponse::create(
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
