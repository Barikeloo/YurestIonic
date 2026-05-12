<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class SaleAlreadyClosedException extends \DomainException
{
    public static function create(): self
    {
        return new self('Sale is already closed.');
    }
}
