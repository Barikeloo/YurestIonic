<?php

namespace App\ProductModifier\Application\DeleteProductModifier;

use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;

class DeleteProductModifier
{
    public function __construct(
        private ProductModifierRepositoryInterface $modifierRepository,
        private EventBusInterface $eventBus,
    ) {}

    public function __invoke(DeleteProductModifierCommand $command): void
    {
        $modifier = $this->modifierRepository->findById($command->id)
            ?? throw ProductModifierNotFoundException::withId($command->id);

        $modifier->delete();
        $events = $modifier->pullDomainEvents();

        $this->modifierRepository->deleteById($command->id);
        $this->eventBus->publish(...$events);
    }
}
