<?php

namespace App\Zone\Application\CreateZone;

use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;

class CreateZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
    ) {}

    public function __invoke(string $name): CreateZoneResponse
    {
        $zone = Zone::dddCreate(ZoneName::create($name));
        $this->zoneRepository->save($zone);

        return CreateZoneResponse::create($zone);
    }
}
