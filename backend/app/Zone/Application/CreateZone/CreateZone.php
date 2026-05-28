<?php

namespace App\Zone\Application\CreateZone;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;

class CreateZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(CreateZoneCommand $command): CreateZoneResponse
    {
        $zone = Zone::dddCreate(ZoneName::create($command->name));
        $this->zoneRepository->save($zone);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('zone.created'),
            entityType: 'zone',
            entityId: $zone->id()->value(),
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'zone_name' => $zone->name()->value(),
            ],
        ));

        return CreateZoneResponse::create(
            id: $zone->id()->value(),
            name: $zone->name()->value(),
            createdAt: $zone->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $zone->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
