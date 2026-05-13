<?php

namespace App\Tables\Domain\Exception;

final class TablesNotInSameZoneException extends \DomainException
{
    public static function create(): self
    {
        return new self('Tables must be in the same zone to be merged.');
    }
}
