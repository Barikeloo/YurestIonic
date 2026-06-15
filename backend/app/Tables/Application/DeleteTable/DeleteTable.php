<?php

namespace App\Tables\Application\DeleteTable;

use App\Shared\Application\Event\EventBusInterface;
use App\Tables\Domain\Exception\TableNotFoundException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

class DeleteTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(DeleteTableCommand $command): void
    {
        $table = $this->tableRepository->findById($command->id)
            ?? throw TableNotFoundException::withId($command->id);

        $table->delete();

        $this->tableRepository->deleteById($table->id()->value());

        $this->eventBus->publish(...$table->pullDomainEvents());
    }
}
