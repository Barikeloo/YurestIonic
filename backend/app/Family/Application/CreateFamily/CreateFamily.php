<?php

namespace App\Family\Application\CreateFamily;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\ValueObject\Uuid;

class CreateFamily
{
    public function __construct(
        private FamilyRepositoryInterface $familyRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(CreateFamilyCommand $command): CreateFamilyResponse
    {
        $family = Family::dddCreate(FamilyName::create($command->name));
        $this->familyRepository->save($family);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('family.created'),
            entityType: 'family',
            entityId: $family->id()->value(),
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'family_name' => $family->name()->value(),
            ],
        ));

        return CreateFamilyResponse::create(
            id: $family->id()->value(),
            name: $family->name()->value(),
            active: $family->isActive(),
            createdAt: $family->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $family->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
