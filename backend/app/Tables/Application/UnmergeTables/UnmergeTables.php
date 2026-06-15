<?php

namespace App\Tables\Application\UnmergeTables;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Tables\Domain\Event\TablesUnmerged;
use App\Tables\Domain\Exception\TableNotFoundException;
use App\Tables\Domain\Exception\TablesWithOpenOrdersException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

final class UnmergeTables
{
    public function __construct(
        private readonly TableRepositoryInterface $tableRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EventBusInterface $eventBus,
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

        $this->eventBus->publish(new TablesUnmerged(
            groupId: $command->groupId,
            tableNames: array_values($tableNames),
        ));

        return UnmergeTablesResponse::create(
            unmergedTableIds: array_map(static fn ($table): string => $table->id()->value(), $tables),
        );
    }
}
