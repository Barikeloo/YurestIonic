<?php

namespace App\Zone\Application\DeleteZone;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;

class DeleteZone
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(DeleteZoneCommand $command): void
    {
        $zone = $this->zoneRepository->findById($command->id)
            ?? throw ZoneNotFoundException::withId($command->id);

        $zoneName = $zone->name()->value();

        $this->zoneRepository->deleteById($zone->id()->value());

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('zone.deleted'),
            entityType: 'zone',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'zone_name' => $zoneName,
            ],
        ));
    }
}
