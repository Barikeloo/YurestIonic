<?php

namespace App\Tables\Application\CreateTable;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Domain\ValueObject\TableName;

class CreateTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
    ) {}

    public function __invoke(string $zoneId, string $name): CreateTableResponse
    {
        $table = Table::dddCreate(
            Uuid::create($zoneId),
            TableName::create($name),
        );

        $this->tableRepository->save($table);

        return CreateTableResponse::create($table);
    }
}
