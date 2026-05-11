<?php

namespace App\SuperAdmin\Domain\Exception;

final class SuperAdminNotAuthenticatedException extends \DomainException
{
    public static function create(): self
    {
        return new self('Not authenticated as superadmin.');
    }
}
