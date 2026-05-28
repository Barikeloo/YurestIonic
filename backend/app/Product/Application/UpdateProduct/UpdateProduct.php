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

        $before = [
            'family_id' => $product->familyId()->value(),
            'tax_id' => $product->taxId()->value(),
            'image_src' => $product->imageSrc()->value(),
            'name' => $product->name()->value(),
            'price' => $product->price()->value(),
            'stock' => $product->stock()->value(),
            'active' => $product->isActive(),
            'allergens' => $product->allergens()->values(),
        ];

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

        $after = [
            'family_id' => $product->familyId()->value(),
            'tax_id' => $product->taxId()->value(),
            'image_src' => $product->imageSrc()->value(),
            'name' => $product->name()->value(),
            'price' => $product->price()->value(),
            'stock' => $product->stock()->value(),
            'active' => $product->isActive(),
            'allergens' => $product->allergens()->values(),
        ];

        $userId = $command->userId !== null ? Uuid::create($command->userId) : null;
        $restaurantId = Uuid::create($command->restaurantId);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $restaurantId,
            slug: ActionSlug::create('product.updated'),
            entityType: 'product',
            entityId: $command->id,
            userId: $userId,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: $before,
            after: $after,
            metadata: [
                'product_name' => $product->name()->value(),
            ],
        ));

        if ($before['price'] !== $command->price) {
            $this->auditRecorder->record(new AuditEventDraft(
                restaurantId: $restaurantId,
                slug: ActionSlug::create('product.price_changed'),
                entityType: 'product',
                entityId: $command->id,
                userId: $userId,
                deviceId: $command->deviceId,
                ipAddress: $command->ipAddress,
                metadata: [
                    'product_name' => $product->name()->value(),
                    'price_before_formatted' => number_format($before['price'] / 100, 2).' €',
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
