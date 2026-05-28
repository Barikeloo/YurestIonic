<?php

namespace App\Tables\Application\MergeTables;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Exception\MinimumTwoTablesRequiredException;
use App\Tables\Domain\Exception\TablesNotFoundException;
use App\Tables\Domain\Exception\TablesNotInSameZoneException;
use App\Tables\Domain\Exception\TablesWithOpenOrdersException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

final class MergeTables
{
    public function __construct(
        private readonly TableRepositoryInterface $tableRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(MergeTablesCommand $command): MergeTablesResponse
    {
        if (count($command->tableIds) < 2) {
            throw MinimumTwoTablesRequiredException::create();
        }

        $inputTables = $this->tableRepository->findByIds($command->tableIds);

        if (count($inputTables) !== count($command->tableIds)) {
            throw TablesNotFoundException::create();
        }

        // Expandir cada mesa fusionada a TODAS las mesas de su grupo
        $allTables = [];
        foreach ($inputTables as $table) {
            if ($table->isMerged()) {
                $groupTables = $this->tableRepository->findByMergedGroupId($table->mergedTableGroupId()->value());
                foreach ($groupTables as $groupTable) {
                    $allTables[$groupTable->id()->value()] = $groupTable;
                }
            } else {
                $allTables[$table->id()->value()] = $table;
            }
        }

        // Si todas las mesas ya están en el mismo grupo, no hacer nada
        $groupIds = array_values(array_unique(
            array_map(
                static fn ($table): ?string => $table->mergedTableGroupId()?->value(),
                $allTables
            )
        ));
        if (count($groupIds) === 1 && $groupIds[0] !== null) {
            $existingGroupId = Uuid::create($groupIds[0]);

            return MergeTablesResponse::create(
                groupId: $existingGroupId->value(),
                mergedTableIds: array_keys($allTables),
            );
        }

        // Validar misma zona
        $firstTable = reset($allTables);
        $zoneId = $firstTable->zoneId();
        foreach ($allTables as $table) {
            if ($table->zoneId()->value() !== $zoneId->value()) {
                throw TablesNotInSameZoneException::create();
            }
        }

        // Validar TO_CHARGE
        foreach ($allTables as $table) {
            $order = $this->orderRepository->findByTableId($table->id());
            if ($order !== null && $order->status()->isToCharge()) {
                throw TablesWithOpenOrdersException::create();
            }
        }

        // Consolidar órdenes abiertas del grupo en una sola
        $openOrders = [];
        foreach ($allTables as $table) {
            $order = $this->orderRepository->findByTableId($table->id());
            if ($order !== null && $order->status()->isOpen()) {
                $openOrders[] = $order;
            }
        }

        if (count($openOrders) > 1) {
            $primaryOrder = $openOrders[0];
            for ($i = 1; $i < count($openOrders); $i++) {
                $secondaryOrder = $openOrders[$i];
                $lines = $this->orderLineRepository->findByOrderId($secondaryOrder->id());
                foreach ($lines as $line) {
                    // clonedForOrder preserva el tipo de línea (producto o menú) y
                    // copia todos los campos relevantes, evitando el branching aquí.
                    $newLine = $line->clonedForOrder(
                        newId: Uuid::generate(),
                        newOrderId: $primaryOrder->id(),
                    );
                    $this->orderLineRepository->save($newLine);
                    $this->orderLineRepository->delete($line->id());
                }
                $this->orderRepository->delete($secondaryOrder->id());
            }
        }

        // Generar nuevo groupId y fusionar TODAS las mesas
        $groupId = Uuid::generate();
        foreach ($allTables as $table) {
            $table->mergeWith($groupId);
            $this->tableRepository->save($table);
        }

        $tableNames = array_map(
            static fn ($table) => $table->name()->value(),
            $allTables
        );

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('table.merged'),
            entityType: 'table_group',
            entityId: $groupId->value(),
            userId: null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'tables_label' => implode(', ', $tableNames),
            ],
        ));

        return MergeTablesResponse::create(
            groupId: $groupId->value(),
            mergedTableIds: array_keys($allTables),
        );
    }
}
