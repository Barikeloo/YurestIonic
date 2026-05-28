<?php

namespace App\Zone\Application\UpdateZone;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;

class UpdateZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(UpdateZoneCommand $command): UpdateZoneResponse
    {
        $zone = $this->zoneRepository->findById($command->id)
            ?? throw ZoneNotFoundException::withId($command->id);

        $before = ['name' => $zone->name()->value()];

        $zone->rename(ZoneName::create($command->name));
        $this->zoneRepository->save($zone);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('zone.updated'),
            entityType: 'zone',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: $before,
            after: ['name' => $zone->name()->value()],
            metadata: [
                'zone_name' => $zone->name()->value(),
            ],
        ));

        return UpdateZoneResponse::create(
            id: $zone->id()->value(),
            name: $zone->name()->value(),
            createdAt: $zone->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $zone->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
