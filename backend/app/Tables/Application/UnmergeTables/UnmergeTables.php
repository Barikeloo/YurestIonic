<?php

namespace App\Tables\Application\UnmergeTables;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Exception\TableNotFoundException;
use App\Tables\Domain\Exception\TablesWithOpenOrdersException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

final class UnmergeTables
{
    public function __construct(
        private readonly TableRepositoryInterface $tableRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(UnmergeTablesCommand $command): UnmergeTablesResponse
    {
        $tables = $this->tableRepository->findByMergedGroupId($command->groupId);

        if (count($tables) === 0) {
            throw TableNotFoundException::withId($command->groupId);
        }

        foreach ($tables as $table) {
            $order = $this->orderRepository->findByTableId($table->id());
            if ($order !== null && $order->status()->isToCharge()) {
                throw TablesWithOpenOrdersException::create();
            }
        }

        $tableNames = array_map(
            static fn ($table): string => $table->name()->value(),
            $tables
        );

        foreach ($tables as $table) {
            $table->unmerge();
            $this->tableRepository->save($table);
        }

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('table.unmerged'),
            entityType: 'table_group',
            entityId: $command->groupId,
            userId: $command->userId !== null ? Uuid::create($command->userId) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'tables_label' => implode(', ', $tableNames),
                'tables_count' => count($tables),
            ],
        ));

        return UnmergeTablesResponse::create(
            unmergedTableIds: array_map(static fn ($table): string => $table->id()->value(), $tables),
        );
    }
}
