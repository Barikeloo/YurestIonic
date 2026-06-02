<?php

namespace App\ProductVariant\Application\CreateProductVariant;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
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
        private readonly AuditRecorderInterface $auditRecorder,
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

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('catalog.variant_created'),
            entityType: 'product_variant',
            entityId: $variant->id()->value(),
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'variant_name' => $variant->name()->value(),
                'price_cents' => $variant->price()->value(),
                'price_formatted' => number_format($variant->price()->value() / 100, 2).' €',
                'product_id' => $variant->productId()->value(),
            ],
        ));

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
