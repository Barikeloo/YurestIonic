<?php

namespace App\Family\Application\UpdateFamily;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Application\Event\EventBusInterface;

class UpdateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateFamilyCommand $command): UpdateFamilyResponse
    {
        $family = $this->familyRepository->findById($command->id)
            ?? throw FamilyNotFoundException::withId($command->id);

        $family->rename(FamilyName::create($command->name));
        $this->familyRepository->save($family);

        $this->eventBus->publish(...$family->pullDomainEvents());

        return UpdateFamilyResponse::create(
            id: $family->id()->value(),
            name: $family->name()->value(),
            active: $family->isActive(),
            createdAt: $family->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $family->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
