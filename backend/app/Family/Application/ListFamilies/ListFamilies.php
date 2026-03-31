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
    public function __invoke(bool $includeDeleted = false): array
    {
        $families = $this->familyRepository->findAll($includeDeleted);

        return array_map(
            static fn ($family): array => ListFamiliesItemResponse::create($family)->toArray(),
            $families,
        );
    }
}
