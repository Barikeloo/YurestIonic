<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Exception;

final class EmailAlreadyRegisteredException extends \DomainException
{
    public static function withEmail(string $email): self
    {
        return new self("The email '{$email}' is already registered for this restaurant.");
    }
}
