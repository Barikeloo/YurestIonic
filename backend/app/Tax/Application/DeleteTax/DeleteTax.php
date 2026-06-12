<?php

namespace App\Tax\Application\DeleteTax;

use App\Shared\Application\Event\EventBusInterface;
use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;

class DeleteTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(DeleteTaxCommand $command): void
    {
        $tax = $this->taxRepository->findById($command->id)
            ?? throw TaxNotFoundException::withId($command->id);

        $tax->delete();

        $this->taxRepository->deleteById($command->id);

        $this->eventBus->publish(...$tax->pullDomainEvents());
    }
}
