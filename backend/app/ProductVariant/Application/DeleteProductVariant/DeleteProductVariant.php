<?php

namespace App\ProductVariant\Application\DeleteProductVariant;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\ProductVariant\Domain\Exception\ProductVariantNotFoundException;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class DeleteProductVariant
{
    public function __construct(
        private ProductVariantRepositoryInterface $variantRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(DeleteProductVariantCommand $command): void
    {
        $variant = $this->variantRepository->findById($command->id)
            ?? throw ProductVariantNotFoundException::withId($command->id);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('catalog.variant_deleted'),
            entityType: 'product_variant',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: [
                'name' => $variant->name()->value(),
                'price' => $variant->price()->value(),
                'stock' => $variant->stock()->value(),
                'active' => $variant->isActive(),
            ],
            metadata: [
                'product_id' => $variant->productId()->value(),
            ],
        ));

        $this->variantRepository->deleteById($command->id);
    }
}
