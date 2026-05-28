<?php

namespace App\Family\Application\UpdateFamily;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\ValueObject\Uuid;

class UpdateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(UpdateFamilyCommand $command): UpdateFamilyResponse
    {
        $family = $this->familyRepository->findById($command->id)
            ?? throw FamilyNotFoundException::withId($command->id);

        $before = ['name' => $family->name()->value()];

        $family->rename(FamilyName::create($command->name));
        $this->familyRepository->save($family);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('family.updated'),
            entityType: 'family',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: $before,
            after: ['name' => $family->name()->value()],
            metadata: [
                'family_name' => $family->name()->value(),
            ],
        ));

        return UpdateFamilyResponse::create(
            id: $family->id()->value(),
            name: $family->name()->value(),
            active: $family->isActive(),
            createdAt: $family->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $family->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
