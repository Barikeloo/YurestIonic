<?php

namespace App\ProductVariant\Application\DeleteProductVariant;

use App\ProductVariant\Domain\Exception\ProductVariantNotFoundException;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;

class DeleteProductVariant
{
    public function __construct(
        private ProductVariantRepositoryInterface $variantRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(DeleteProductVariantCommand $command): void
    {
        $variant = $this->variantRepository->findById($command->id)
            ?? throw ProductVariantNotFoundException::withId($command->id);

        $variant->delete();

        $events = $variant->pullDomainEvents();

        $this->variantRepository->deleteById($command->id);

        $this->eventBus->publish(...$events);
    }
}
