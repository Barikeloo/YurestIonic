<?php

namespace App\Product\Application\SetProductActive;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;

class SetProductActive
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private EventBusInterface $eventBus,
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
        $this->eventBus->publish(...$product->pullDomainEvents());

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
