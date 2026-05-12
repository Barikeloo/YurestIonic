<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class SalePaymentsNotFoundException extends \DomainException
{
    public static function create(): self
    {
        return new self('No payments found for this sale');
    }
}
