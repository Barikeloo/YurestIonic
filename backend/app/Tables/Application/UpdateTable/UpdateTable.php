<?php

namespace App\Tables\Application\UpdateTable;

use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Exception\TableNameAlreadyExistsInZoneException;
use App\Tables\Domain\Exception\TableNotFoundException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Domain\ValueObject\TableName;

class UpdateTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateTableCommand $command): UpdateTableResponse
    {
        $table = $this->tableRepository->findById($command->id)
            ?? throw TableNotFoundException::withId($command->id);

        $zoneIdVO = Uuid::create($command->zoneId);

        if ($this->tableRepository->findByZoneIdAndName($zoneIdVO, $command->name, $command->id) !== null) {
            throw TableNameAlreadyExistsInZoneException::withName($command->name);
        }

        $table->update(
            $zoneIdVO,
            TableName::create($command->name),
        );

        $this->tableRepository->save($table);

        $this->eventBus->publish(...$table->pullDomainEvents());

        return UpdateTableResponse::create(
            id:        $table->id()->value(),
            zoneId:    $table->zoneId()->value(),
            name:      $table->name()->value(),
            createdAt: $table->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $table->updatedAt()->format(\DateTimeInterface::ATOM),
            layout:    $table->layout()?->toArray(),
        );
    }
}
