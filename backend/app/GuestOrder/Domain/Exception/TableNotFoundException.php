<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Exception;

final class TableNotFoundException extends \DomainException
{
    public static function withId(string $tableId): self
    {
        return new self("Table '{$tableId}' not found.");
    }
}
