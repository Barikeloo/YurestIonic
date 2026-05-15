<?php

namespace App\ProductModifier\Application\UpdateProductModifier;

use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\ProductModifier\Domain\ValueObject\ModifierName;
use App\ProductModifier\Domain\ValueObject\ModifierPrice;
use App\ProductModifier\Domain\ValueObject\ModifierSelectionType;
use App\ProductModifier\Domain\ValueObject\ModifierType;

class UpdateProductModifier
{
    public function __construct(
        private ProductModifierRepositoryInterface $modifierRepository,
    ) {}

    public function __invoke(UpdateProductModifierCommand $command): UpdateProductModifierResponse
    {
        $modifier = $this->modifierRepository->findById($command->id)
            ?? throw ProductModifierNotFoundException::withId($command->id);

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
