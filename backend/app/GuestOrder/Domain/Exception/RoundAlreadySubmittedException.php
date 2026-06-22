<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Exception;

final class RoundAlreadySubmittedException extends \DomainException
{
    public static function withKey(string $key): self
    {
        return new self("Round with idempotency key '{$key}' was already submitted.");
    }
}
