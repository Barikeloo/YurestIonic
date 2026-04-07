<?php

namespace App\Tables\Application\CreateTable;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Domain\ValueObject\TableName;
use InvalidArgumentException;

class CreateTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
    ) {}

    public function __invoke(string $zoneId, string $name): CreateTableResponse
    {
        // Validar que no exista otra mesa con el mismo nombre en esta zona
        $existingTable = $this->tableRepository->findByZoneIdAndName($zoneId, $name);
        if ($existingTable !== null) {
            throw new InvalidArgumentException('Ya existe una mesa con ese nombre en esta zona.');
        }

        $table = Table::dddCreate(
            Uuid::create($zoneId),
            TableName::create($name),
        );

        $this->tableRepository->save($table);

        return CreateTableResponse::create($table);
    }
}
