<?php

namespace App\Tables\Application\MergeTables;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Event\TablesMerged;
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
        private readonly EventBusInterface $eventBus,
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

        $firstTable = reset($allTables);
        $zoneId = $firstTable->zoneId();
        foreach ($allTables as $table) {
            if ($table->zoneId()->value() !== $zoneId->value()) {
                throw TablesNotInSameZoneException::create();
            }
        }

        foreach ($allTables as $table) {
            $order = $this->orderRepository->findByTableId($table->id());
            if ($order !== null && $order->status()->isToCharge()) {
                throw TablesWithOpenOrdersException::create();
            }
        }

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

        $groupId = Uuid::generate();
        foreach ($allTables as $table) {
            $table->mergeWith($groupId);
            $this->tableRepository->save($table);
        }

        $tableNames = array_values(array_map(
            static fn ($table): string => $table->name()->value(),
            $allTables
        ));

        $this->eventBus->publish(new TablesMerged(
            groupId: $groupId->value(),
            tableNames: $tableNames,
        ));

        return MergeTablesResponse::create(
            groupId: $groupId->value(),
            mergedTableIds: array_keys($allTables),
        );
    }
}
