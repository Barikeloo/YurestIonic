<?php

namespace App\ProductModifier\Application\CreateProductModifier;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\ProductModifier\Domain\ValueObject\ModifierName;
use App\ProductModifier\Domain\ValueObject\ModifierPrice;
use App\ProductModifier\Domain\ValueObject\ModifierSelectionType;
use App\ProductModifier\Domain\ValueObject\ModifierType;
use App\Shared\Domain\ValueObject\Uuid;

class CreateProductModifier
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductModifierRepositoryInterface $modifierRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(CreateProductModifierCommand $command): CreateProductModifierResponse
    {
        $product = $this->productRepository->findById($command->productId)
            ?? throw ProductNotFoundException::withId($command->productId);

        $modifier = ProductModifier::dddCreate(
            productId: Uuid::create($command->productId),
            name: ModifierName::create($command->name),
            type: ModifierType::create($command->type),
            isRequired: $command->isRequired,
            selectionType: ModifierSelectionType::create($command->selectionType),
            price: ModifierPrice::create($command->price),
            active: $command->active,
            sortOrder: $command->sortOrder,
        );

        $this->modifierRepository->save($modifier);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('catalog.modifier_created'),
            entityType: 'product_modifier',
            entityId: $modifier->id()->value(),
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'modifier_name' => $modifier->name()->value(),
                'modifier_type' => $modifier->type()->value(),
                'price_cents' => $modifier->price()->value(),
                'price_formatted' => number_format($modifier->price()->value() / 100, 2).' €',
                'product_id' => $modifier->productId()->value(),
            ],
        ));

        return CreateProductModifierResponse::create(
            id: $modifier->id()->value(),
            productId: $modifier->productId()->value(),
            name: $modifier->name()->value(),
            type: $modifier->type()->value(),
            isRequired: $modifier->isRequired(),
            selectionType: $modifier->selectionType()->value(),
            price: $modifier->price()->value(),
            active: $modifier->isActive(),
            sortOrder: $modifier->sortOrder(),
            createdAt: $modifier->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $modifier->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
