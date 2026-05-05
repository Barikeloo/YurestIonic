<?php

namespace App\User\Domain\Exception;

final class RestaurantNotFoundException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Restaurant not found.');
    }
}
