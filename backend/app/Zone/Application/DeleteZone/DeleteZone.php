<?php

namespace App\Zone\Application\DeleteZone;

use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;

class DeleteZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
    ) {}

    public function __invoke(string $id): bool
    {
        return $this->zoneRepository->deleteById($id);
    }
}
