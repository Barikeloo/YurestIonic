<?php

namespace App\Family\Application\SetFamilyActive;

use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

class SetFamilyActive
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(SetFamilyActiveCommand $command): SetFamilyActiveResponse
    {
        $family = $this->familyRepository->findById($command->id)
            ?? throw FamilyNotFoundException::withId($command->id);

        if ($command->active) {
            $family->activate();
        } else {
            $family->deactivate();
        }

        $this->familyRepository->save($family);

        return SetFamilyActiveResponse::create(
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
