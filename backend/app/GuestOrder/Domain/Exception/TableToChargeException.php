<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Exception;

final class TableToChargeException extends \DomainException
{
    public static function create(): self
    {
        return new self("This table is being closed. No new orders can be placed.");
    }
}
