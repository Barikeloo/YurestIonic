<?php

declare(strict_types=1);

namespace App\Family\Application\ListFamilies;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

class ListFamilies
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(ListFamiliesCommand $command): ListFamiliesResponse
    {
        $families = $this->familyRepository->findAll($command->includeDeleted ?? false);

        if ($command->onlyActive ?? false) {
            $families = array_filter($families, static fn (Family $f): bool => $f->isActive());
        }

        $items = array_map(
            static fn (Family $family): array => ListFamiliesItemResponse::create(
                id: $family->id()->value(),
                name: $family->name()->value(),
                color: $family->color()?->value(),
                icon: $family->icon()?->value(),
                active: $family->isActive(),
                createdAt: $family->createdAt()->format(\DateTimeInterface::ATOM),
                updatedAt: $family->updatedAt()->format(\DateTimeInterface::ATOM),
            )->toArray(),
            $families,
        );

        return ListFamiliesResponse::create(
            items: array_values($items),
        );
    }
}
