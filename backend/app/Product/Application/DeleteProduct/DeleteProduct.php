<?php

namespace App\Product\Application\DeleteProduct;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class DeleteProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(DeleteProductCommand $command): void
    {
        $product = $this->productRepository->findById($command->id)
            ?? throw ProductNotFoundException::withId($command->id);

        $productName = $product->name()->value();
        $priceCents = $product->price()->value();

        $deleted = $this->productRepository->deleteById($command->id);

        if (! $deleted) {
            throw ProductNotFoundException::withId($command->id);
        }

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('product.deleted'),
            entityType: 'product',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'product_name' => $productName,
                'price_cents' => $priceCents,
                'price_formatted' => number_format($priceCents / 100, 2).' €',
            ],
        ));
    }
}
