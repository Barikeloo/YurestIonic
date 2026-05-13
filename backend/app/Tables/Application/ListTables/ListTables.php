<?php

namespace App\Tables\Application\ListTables;

use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

class ListTables
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
    ) {}

    public function __invoke(ListTablesCommand $command): ListTablesResponse
    {
        $tables = $this->tableRepository->findAll($command->includeDeleted ?? false);

        $items = array_map(
            static fn (Table $table): array => ListTablesItemResponse::create(
                id: $table->id()->value(),
                zoneId: $table->zoneId()->value(),
                name: $table->name()->value(),
                createdAt: $table->createdAt()->format(\DateTimeInterface::ATOM),
                updatedAt: $table->updatedAt()->format(\DateTimeInterface::ATOM),
                mergedTableGroupId: $table->mergedTableGroupId()?->value(),
            )->toArray(),
            $tables,
        );

        return ListTablesResponse::create(
            items: $items,
        );
    }
}
