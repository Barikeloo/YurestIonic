<?php

declare(strict_types=1);

namespace App\Cash\Domain\Exception;

final class CashSessionAlreadyClosedException extends \DomainException
{
    public static function create(): self
    {
        return new self('Session is already closed or abandoned.');
    }
}
