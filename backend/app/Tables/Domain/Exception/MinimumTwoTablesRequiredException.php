<?php

namespace App\Tables\Domain\Exception;

final class MinimumTwoTablesRequiredException extends \DomainException
{
    public static function create(): self
    {
        return new self('At least 2 tables are required to merge.');
    }
}
