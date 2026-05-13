<?php

namespace App\Tables\Application\MergeTables;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Exception\MinimumTwoTablesRequiredException;
use App\Tables\Domain\Exception\TablesAlreadyMergedException;
use App\Tables\Domain\Exception\TablesNotInSameZoneException;
use App\Tables\Domain\Exception\TablesNotFoundException;
use App\Tables\Domain\Exception\TablesWithOpenOrdersException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

final class MergeTables
{
    public function __construct(
        private readonly TableRepositoryInterface $tableRepository,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(MergeTablesCommand $command): MergeTablesResponse
    {
        if (count($command->tableIds) < 2) {
            throw MinimumTwoTablesRequiredException::create();
        }

        $tables = $this->tableRepository->findByIds($command->tableIds);

        if (count($tables) !== count($command->tableIds)) {
            throw TablesNotFoundException::create();
        }

        $firstTable = $tables[0];
        $zoneId = $firstTable->zoneId();

        foreach ($tables as $table) {
            if ($table->zoneId()->value() !== $zoneId->value()) {
                throw TablesNotInSameZoneException::create();
            }

            if ($table->isMerged()) {
                throw TablesAlreadyMergedException::create($table->id()->value());
            }

            $order = $this->orderRepository->findByTableId($table->id());
            if ($order !== null && $order->status()->isToCharge()) {
                throw TablesWithOpenOrdersException::create();
            }
        }

        $groupId = Uuid::generate();

        foreach ($tables as $table) {
            $table->mergeWith($groupId);
            $this->tableRepository->save($table);
        }

        return MergeTablesResponse::create(
            groupId: $groupId->value(),
            mergedTableIds: array_map(static fn ($table): string => $table->id()->value(), $tables),
        );
    }
}
