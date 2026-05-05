<?php

namespace App\User\Domain\Exception;

final class InvalidCredentialsException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }
}
