<?php

namespace App\Restaurant\Domain\Exception;

final class NotAuthenticatedException extends \DomainException
{
    public static function create(): self
    {
        return new self('Not authenticated.');
    }
}
