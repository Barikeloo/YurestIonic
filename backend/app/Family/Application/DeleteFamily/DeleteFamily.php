<?php

namespace App\Family\Application\DeleteFamily;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class DeleteFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(DeleteFamilyCommand $command): void
    {
        $family = $this->familyRepository->findById($command->id)
            ?? throw FamilyNotFoundException::withId($command->id);

        $familyName = $family->name()->value();

        $this->familyRepository->deleteById($family->id()->value());

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('family.deleted'),
            entityType: 'family',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'family_name' => $familyName,
            ],
        ));
    }
}
