<?php

namespace App\Zone\Application\GetZone;

use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;

class GetZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
    ) {}

    public function __invoke(string $id): ?GetZoneResponse
    {
        $zone = $this->zoneRepository->findById($id);

        if ($zone === null) {
            return null;
        }

        return GetZoneResponse::create($zone);
    }
}
