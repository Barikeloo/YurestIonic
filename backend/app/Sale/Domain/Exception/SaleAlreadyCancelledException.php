<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class SaleAlreadyCancelledException extends \DomainException
{
    public static function create(): self
    {
        return new self('Sale is already cancelled.');
    }
}
