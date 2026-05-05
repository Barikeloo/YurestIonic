<?php

declare(strict_types=1);

namespace App\Family\Application\ListFamilies;

use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

class ListFamilies
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(bool $includeDeleted = false, bool $onlyActive = false): array
    {
        $families = $this->familyRepository->findAll($includeDeleted);

        if ($onlyActive) {
            $families = array_filter($families, fn ($f) => $f->isActive());
        }

        return array_map(
            static fn ($family): array => ListFamiliesItemResponse::create($family)->toArray(),
            $families,
        );
    }
}
