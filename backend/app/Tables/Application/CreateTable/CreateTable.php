<?php

namespace App\Tables\Application\CreateTable;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Exception\TableNameAlreadyExistsInZoneException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Domain\ValueObject\TableName;

class CreateTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(CreateTableCommand $command): CreateTableResponse
    {
        $zoneIdVO = Uuid::create($command->zoneId);

        if ($this->tableRepository->findByZoneIdAndName($zoneIdVO, $command->name) !== null) {
            throw TableNameAlreadyExistsInZoneException::withName($command->name);
        }

        $table = Table::dddCreate(
            $zoneIdVO,
            TableName::create($command->name),
        );

        $this->tableRepository->save($table);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('table.created'),
            entityType: 'table',
            entityId: $table->id()->value(),
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'table_name' => $table->name()->value(),
                'zone_id' => $table->zoneId()->value(),
            ],
        ));

        return CreateTableResponse::create(
            id: $table->id()->value(),
            zoneId: $table->zoneId()->value(),
            name: $table->name()->value(),
            createdAt: $table->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $table->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
