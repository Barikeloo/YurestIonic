<?php

namespace App\Restaurant\Domain\Exception;

final class RestaurantNotFoundException extends \DomainException
{
    public static function withUuid(string $uuid): self
    {
        return new self("Restaurant with uuid {$uuid} not found.");
    }
}
