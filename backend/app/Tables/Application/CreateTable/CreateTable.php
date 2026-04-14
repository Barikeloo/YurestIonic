<?php

namespace App\Tables\Application\CreateTable;

use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Domain\ValueObject\TableName;
use App\Tables\Domain\ValueObject\ZoneId;
use InvalidArgumentException;

class CreateTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
    ) {}

    public function __invoke(string $zoneId, string $name): CreateTableResponse
    {
        $zoneIdVO = ZoneId::create($zoneId);

        $existingTable = $this->tableRepository->findByZoneIdAndName($zoneIdVO, $name);
        if ($existingTable !== null) {
            throw new InvalidArgumentException('Ya existe una mesa con ese nombre en esta zona.');
        }

        $table = Table::dddCreate(
            $zoneIdVO,
            TableName::create($name),
        );

        $this->tableRepository->save($table);

        return CreateTableResponse::create($table);
    }
}
