<?php

namespace App\Tables\Domain\Exception;

final class TablesNotFoundException extends \DomainException
{
    public static function create(): self
    {
        return new self('One or more tables not found.');
    }
}
