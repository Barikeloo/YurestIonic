<?php

namespace App\Restaurant\Domain\Exception;

final class LinkedRestaurantNotFoundException extends \DomainException
{
    public static function create(): self
    {
        return new self('Linked restaurant not found.');
    }
}
