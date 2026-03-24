<?php

namespace App\Family\Application\UpdateFamily;

use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;

class UpdateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $id, string $name): ?UpdateFamilyResponse
    {
        $family = $this->familyRepository->findById($id);

        if ($family === null) {
            return null;
        }

        $family->rename(FamilyName::create($name));
        $this->familyRepository->save($family);

        return UpdateFamilyResponse::create($family);
    }
}
