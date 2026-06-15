<?php

namespace App\Tables\Application\CreateTable;

use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Exception\TableNameAlreadyExistsInZoneException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Domain\ValueObject\TableName;

class CreateTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
        private readonly EventBusInterface $eventBus,
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

        $this->eventBus->publish(...$table->pullDomainEvents());

        return CreateTableResponse::create(
            id: $table->id()->value(),
            zoneId: $table->zoneId()->value(),
            name: $table->name()->value(),
            createdAt: $table->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $table->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
