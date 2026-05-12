<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class InvalidDinerCountException extends \DomainException
{
    public static function create(): self
    {
        return new self('Diners count must be greater than 0.');
    }

    public static function invalidDinerNumber(): self
    {
        return new self('Invalid diner number.');
    }
}
