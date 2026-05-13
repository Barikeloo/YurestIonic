<?php

namespace App\Tables\Domain\Exception;

final class TableHasActiveDinersException extends \DomainException
{
    public static function create(string $tableName): self
    {
        return new self(sprintf('Table %s has active diners and cannot be merged.', $tableName));
    }
}
