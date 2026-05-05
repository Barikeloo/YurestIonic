<?php

namespace App\User\Domain\Exception;

final class NotAuthenticatedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Not authenticated.');
    }
}
