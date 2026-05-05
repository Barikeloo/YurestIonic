<?php

namespace App\User\Domain\Exception;

final class ForbiddenRestaurantAccessException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Forbidden.');
    }
}
