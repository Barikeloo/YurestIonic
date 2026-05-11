<?php

namespace App\SuperAdmin\Domain\Exception;

final class InvalidSuperAdminCredentialsException extends \DomainException
{
    public static function create(): self
    {
        return new self('Invalid superadmin credentials.');
    }
}
