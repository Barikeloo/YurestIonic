<?php

namespace App\Zone\Application\UpdateZone;

use App\Shared\Application\Event\EventBusInterface;
use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;

class UpdateZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateZoneCommand $command): UpdateZoneResponse
    {
        $zone = $this->zoneRepository->findById($command->id)
            ?? throw ZoneNotFoundException::withId($command->id);

        $zone->rename(ZoneName::create($command->name));
        $this->zoneRepository->save($zone);

        $this->eventBus->publish(...$zone->pullDomainEvents());

        return UpdateZoneResponse::create(
            id: $zone->id()->value(),
            name: $zone->name()->value(),
            createdAt: $zone->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $zone->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
