<?php

namespace App\Zone\Application\DeleteZone;

use App\Shared\Application\Event\EventBusInterface;
use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;

class DeleteZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(DeleteZoneCommand $command): void
    {
        $zone = $this->zoneRepository->findById($command->id)
            ?? throw ZoneNotFoundException::withId($command->id);

        $zone->delete();

        $this->zoneRepository->deleteById($zone->id()->value());

        $this->eventBus->publish(...$zone->pullDomainEvents());
    }
}
