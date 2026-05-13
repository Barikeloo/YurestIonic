<?php

namespace App\Restaurant\Domain\Exception;

final class ForbiddenException extends \DomainException
{
    public static function create(): self
    {
        return new self('Forbidden for this restaurant context.');
    }
}
