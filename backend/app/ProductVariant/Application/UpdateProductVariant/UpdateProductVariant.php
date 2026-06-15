<?php

namespace App\ProductVariant\Application\UpdateProductVariant;

use App\ProductVariant\Domain\Exception\ProductVariantNotFoundException;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\ProductVariant\Domain\ValueObject\VariantName;
use App\ProductVariant\Domain\ValueObject\VariantPrice;
use App\ProductVariant\Domain\ValueObject\VariantStock;
use App\Shared\Application\Event\EventBusInterface;

class UpdateProductVariant
{
    public function __construct(
        private ProductVariantRepositoryInterface $variantRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateProductVariantCommand $command): UpdateProductVariantResponse
    {
        $variant = $this->variantRepository->findById($command->id)
            ?? throw ProductVariantNotFoundException::withId($command->id);

        $variant->update(
            name: VariantName::create($command->name),
            price: VariantPrice::create($command->price),
            stock: VariantStock::create($command->stock),
            active: $command->active,
            sortOrder: $command->sortOrder,
        );

        $events = $variant->pullDomainEvents();

        $this->variantRepository->save($variant);

        $this->eventBus->publish(...$events);

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
