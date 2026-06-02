<?php

namespace App\ProductModifier\Application\UpdateProductModifier;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\ProductModifier\Domain\ValueObject\ModifierName;
use App\ProductModifier\Domain\ValueObject\ModifierPrice;
use App\ProductModifier\Domain\ValueObject\ModifierSelectionType;
use App\ProductModifier\Domain\ValueObject\ModifierType;
use App\Shared\Domain\ValueObject\Uuid;

class UpdateProductModifier
{
    public function __construct(
        private ProductModifierRepositoryInterface $modifierRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(UpdateProductModifierCommand $command): UpdateProductModifierResponse
    {
        $modifier = $this->modifierRepository->findById($command->id)
            ?? throw ProductModifierNotFoundException::withId($command->id);

        $before = [
            'name' => $modifier->name()->value(),
            'type' => $modifier->type()->value(),
            'is_required' => $modifier->isRequired(),
            'selection_type' => $modifier->selectionType()->value(),
            'price' => $modifier->price()->value(),
            'active' => $modifier->isActive(),
            'sort_order' => $modifier->sortOrder(),
        ];

        $modifier->update(
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
            slug: ActionSlug::create('catalog.modifier_updated'),
            entityType: 'product_modifier',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: $before,
            after: [
                'name' => $modifier->name()->value(),
                'type' => $modifier->type()->value(),
                'is_required' => $modifier->isRequired(),
                'selection_type' => $modifier->selectionType()->value(),
                'price' => $modifier->price()->value(),
                'active' => $modifier->isActive(),
                'sort_order' => $modifier->sortOrder(),
            ],
            metadata: [
                'modifier_name' => $modifier->name()->value(),
            ],
        ));

        return UpdateProductModifierResponse::create(
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
