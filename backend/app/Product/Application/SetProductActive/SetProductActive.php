<?php

namespace App\Product\Application\SetProductActive;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class SetProductActive
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(SetProductActiveCommand $command): SetProductActiveResponse
    {
        $product = $this->productRepository->findById($command->id)
            ?? throw ProductNotFoundException::withId($command->id);

        if ($command->active) {
            $product->activate();
        } else {
            $product->deactivate();
        }

        $this->productRepository->save($product);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create($command->active ? 'product.activated' : 'product.deactivated'),
            entityType: 'product',
            entityId: $command->id,
            userId: null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'product_name' => $product->name()->value(),
            ],
        ));

        return SetProductActiveResponse::create(
            id: $product->id()->value(),
            familyId: $product->familyId()->value(),
            taxId: $product->taxId()->value(),
            imageSrc: $product->imageSrc()->value(),
            name: $product->name()->value(),
            price: $product->price()->value(),
            stock: $product->stock()->value(),
            active: $product->isActive(),
            createdAt: $product->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $product->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
