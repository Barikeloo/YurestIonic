<?php

namespace App\Family\Application\GetFamily;

use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

class GetFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $id): ?GetFamilyResponse
    {
        $family = $this->familyRepository->findById($id);

        if ($family === null) {
            return null;
        }

        return GetFamilyResponse::create($family);
    }
}
