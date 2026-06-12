<?php

namespace App\Family\Application\DeleteFamily;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;

class DeleteFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(DeleteFamilyCommand $command): void
    {
        $family = $this->familyRepository->findById($command->id)
            ?? throw FamilyNotFoundException::withId($command->id);

        $family->delete();

        $this->familyRepository->deleteById($family->id()->value());

        $this->eventBus->publish(...$family->pullDomainEvents());
    }
}
