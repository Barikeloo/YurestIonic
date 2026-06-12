<?php

namespace App\Zone\Application\CreateZone;

use App\Shared\Application\Event\EventBusInterface;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;

class CreateZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(CreateZoneCommand $command): CreateZoneResponse
    {
        $zone = Zone::dddCreate(ZoneName::create($command->name));
        $this->zoneRepository->save($zone);

        $this->eventBus->publish(...$zone->pullDomainEvents());

        return CreateZoneResponse::create(
            id: $zone->id()->value(),
            name: $zone->name()->value(),
            createdAt: $zone->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $zone->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
