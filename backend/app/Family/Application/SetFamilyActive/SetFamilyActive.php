<?php

namespace App\Family\Application\SetFamilyActive;

use App\Family\Domain\Interfaces\FamilyRepositoryInterface;

class SetFamilyActive
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
    ) {}

    public function __invoke(string $id, bool $active): ?SetFamilyActiveResponse
    {
        $family = $this->familyRepository->findById($id);

        if ($family === null) {
            return null;
        }

        if ($active) {
            $family->activate();
        } else {
            $family->deactivate();
        }

        $this->familyRepository->save($family);

        return SetFamilyActiveResponse::create($family);
    }
}
