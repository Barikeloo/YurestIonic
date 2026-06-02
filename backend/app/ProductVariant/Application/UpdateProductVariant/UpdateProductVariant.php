<?php

namespace App\ProductVariant\Application\UpdateProductVariant;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\ProductVariant\Domain\Exception\ProductVariantNotFoundException;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\ProductVariant\Domain\ValueObject\VariantName;
use App\ProductVariant\Domain\ValueObject\VariantPrice;
use App\ProductVariant\Domain\ValueObject\VariantStock;
use App\Shared\Domain\ValueObject\Uuid;

class UpdateProductVariant
{
    public function __construct(
        private ProductVariantRepositoryInterface $variantRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(UpdateProductVariantCommand $command): UpdateProductVariantResponse
    {
        $variant = $this->variantRepository->findById($command->id)
            ?? throw ProductVariantNotFoundException::withId($command->id);

        $before = [
            'name' => $variant->name()->value(),
            'price' => $variant->price()->value(),
            'stock' => $variant->stock()->value(),
            'active' => $variant->isActive(),
            'sort_order' => $variant->sortOrder(),
        ];

        $variant->update(
            name: VariantName::create($command->name),
            price: VariantPrice::create($command->price),
            stock: VariantStock::create($command->stock),
            active: $command->active,
            sortOrder: $command->sortOrder,
        );

        $this->variantRepository->save($variant);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('catalog.variant_updated'),
            entityType: 'product_variant',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: $before,
            after: [
                'name' => $variant->name()->value(),
                'price' => $variant->price()->value(),
                'stock' => $variant->stock()->value(),
                'active' => $variant->isActive(),
                'sort_order' => $variant->sortOrder(),
            ],
            metadata: [
                'variant_name' => $variant->name()->value(),
            ],
        ));

        return UpdateProductVariantResponse::create(
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
