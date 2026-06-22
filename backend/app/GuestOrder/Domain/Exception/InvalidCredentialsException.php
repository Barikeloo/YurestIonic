<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Exception;

final class InvalidCredentialsException extends \DomainException
{
    public static function create(): self
    {
        return new self("Invalid email or password.");
    }
}
