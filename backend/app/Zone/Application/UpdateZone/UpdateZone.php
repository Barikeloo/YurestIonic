<?php

namespace App\Zone\Application\UpdateZone;

use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;

class UpdateZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
    ) {}

    public function __invoke(string $id, string $name): ?UpdateZoneResponse
    {
        $zone = $this->zoneRepository->findById($id);

        if ($zone === null) {
            return null;
        }

        $zone->rename(ZoneName::create($name));
        $this->zoneRepository->save($zone);

        return UpdateZoneResponse::create($zone);
    }
}
