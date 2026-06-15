<?php

namespace App\Product\Application\DeleteProduct;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;

class DeleteProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private EventBusInterface $eventBus,
    ) {}

    public function __invoke(DeleteProductCommand $command): void
    {
        $product = $this->productRepository->findById($command->id)
            ?? throw ProductNotFoundException::withId($command->id);

        $product->delete();
        $events = $product->pullDomainEvents();

        $deleted = $this->productRepository->deleteById($command->id);

        if (! $deleted) {
            throw ProductNotFoundException::withId($command->id);
        }

        $this->eventBus->publish(...$events);
    }
}
