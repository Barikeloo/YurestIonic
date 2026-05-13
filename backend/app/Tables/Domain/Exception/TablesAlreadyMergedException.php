<?php

namespace App\Tables\Domain\Exception;

final class TablesAlreadyMergedException extends \DomainException
{
    public static function create(string $tableId): self
    {
        return new self(sprintf('Table %s is already merged with other tables.', $tableId));
    }
}
