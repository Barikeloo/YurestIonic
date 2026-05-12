<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class ChargeSessionHasNoRemainingDebtException extends \DomainException
{
    public static function create(): self
    {
        return new self('Charge session has no remaining debt.');
    }
}
