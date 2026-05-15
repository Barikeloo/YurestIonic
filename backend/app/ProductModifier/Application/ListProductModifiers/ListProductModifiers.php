<?php

namespace App\ProductModifier\Application\ListProductModifiers;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;

class ListProductModifiers
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductModifierRepositoryInterface $modifierRepository,
    ) {}

    public function __invoke(ListProductModifiersCommand $command): ListProductModifiersResponse
    {
        $product = $this->productRepository->findById($command->productId)
            ?? throw ProductNotFoundException::withId($command->productId);

        $modifiers = $this->modifierRepository->findByProductId($command->productId);

        return ListProductModifiersResponse::create(
            modifiers: array_map(
                static fn (ProductModifier $modifier): array => [
                    'id' => $modifier->id()->value(),
                    'product_id' => $modifier->productId()->value(),
                    'name' => $modifier->name()->value(),
                    'type' => $modifier->type()->value(),
                    'is_required' => $modifier->isRequired(),
                    'selection_type' => $modifier->selectionType()->value(),
                    'price' => $modifier->price()->value(),
                    'active' => $modifier->isActive(),
                    'sort_order' => $modifier->sortOrder(),
                    'created_at' => $modifier->createdAt()->format(\DateTimeInterface::ATOM),
                    'updated_at' => $modifier->updatedAt()->format(\DateTimeInterface::ATOM),
                ],
                $modifiers,
            ),
        );
    }
}
