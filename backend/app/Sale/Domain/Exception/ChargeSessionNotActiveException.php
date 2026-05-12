<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class ChargeSessionNotActiveException extends \DomainException
{
    public static function create(): self
    {
        return new self('Charge session is not active.');
    }
}
