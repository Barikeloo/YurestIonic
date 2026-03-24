<?php

namespace App\Family\Application\ListFamilies;

use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

class ListFamilies
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    /**
     * @return array<int, array<string, bool|string>>
     */
    public function __invoke(): array
    {
        $families = $this->familyRepository->findAll();

        return array_map(
            static fn ($family): array => ListFamiliesItemResponse::create($family)->toArray(),
            $families,
        );
    }
}
