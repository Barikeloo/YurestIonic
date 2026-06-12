<?php

namespace App\Family\Application\CreateFamily;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyColor;
use App\Family\Domain\ValueObject\FamilyIcon;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Application\Event\EventBusInterface;

class CreateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(CreateFamilyCommand $command): CreateFamilyResponse
    {
        $family = Family::dddCreate(
            FamilyName::create($command->name),
            $command->color !== null ? FamilyColor::create($command->color) : null,
            $command->icon !== null ? FamilyIcon::create($command->icon) : null,
        );
        $this->familyRepository->save($family);

        $this->eventBus->publish(...$family->pullDomainEvents());

        return CreateFamilyResponse::create(
            id: $family->id()->value(),
            name: $family->name()->value(),
            color: $family->color()?->value(),
            icon: $family->icon()?->value(),
            active: $family->isActive(),
            createdAt: $family->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $family->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
