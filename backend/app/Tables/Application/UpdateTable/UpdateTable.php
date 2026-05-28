<?php

namespace App\Tables\Application\UpdateTable;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Exception\TableNameAlreadyExistsInZoneException;
use App\Tables\Domain\Exception\TableNotFoundException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Domain\ValueObject\TableName;

class UpdateTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(UpdateTableCommand $command): UpdateTableResponse
    {
        $table = $this->tableRepository->findById($command->id)
            ?? throw TableNotFoundException::withId($command->id);

        $zoneIdVO = Uuid::create($command->zoneId);

        if ($this->tableRepository->findByZoneIdAndName($zoneIdVO, $command->name, $command->id) !== null) {
            throw TableNameAlreadyExistsInZoneException::withName($command->name);
        }

        $before = [
            'zone_id' => $table->zoneId()->value(),
            'name' => $table->name()->value(),
        ];

        $table->update(
            $zoneIdVO,
            TableName::create($command->name),
        );

        $this->tableRepository->save($table);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('table.updated'),
            entityType: 'table',
            entityId: $table->id()->value(),
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: $before,
            after: [
                'zone_id' => $table->zoneId()->value(),
                'name' => $table->name()->value(),
            ],
            metadata: [
                'table_name' => $table->name()->value(),
            ],
        ));

        return UpdateTableResponse::create(
            id: $table->id()->value(),
            zoneId: $table->zoneId()->value(),
            name: $table->name()->value(),
            createdAt: $table->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $table->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
