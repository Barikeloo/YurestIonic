<?php

namespace App\ProductModifier\Application\CreateProductModifier;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\ProductModifier\Domain\ValueObject\ModifierName;
use App\ProductModifier\Domain\ValueObject\ModifierPrice;
use App\ProductModifier\Domain\ValueObject\ModifierSelectionType;
use App\ProductModifier\Domain\ValueObject\ModifierType;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

class CreateProductModifier
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductModifierRepositoryInterface $modifierRepository,
        private EventBusInterface $eventBus,
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
        $this->eventBus->publish(...$modifier->pullDomainEvents());

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
