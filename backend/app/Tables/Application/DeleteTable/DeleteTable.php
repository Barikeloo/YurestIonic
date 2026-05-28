<?php

namespace App\Tables\Application\DeleteTable;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Exception\TableNotFoundException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

class DeleteTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(DeleteTableCommand $command): void
    {
        $table = $this->tableRepository->findById($command->id)
            ?? throw TableNotFoundException::withId($command->id);

        $tableName = $table->name()->value();
        $zoneId = $table->zoneId()->value();

        $this->tableRepository->deleteById($table->id()->value());

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('table.deleted'),
            entityType: 'table',
            entityId: $command->id,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'table_name' => $tableName,
                'zone_id' => $zoneId,
            ],
        ));
    }
}
