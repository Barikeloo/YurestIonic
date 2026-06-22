<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Exception;

final class GuestSessionNotFoundException extends \DomainException
{
    public static function withToken(string $token): self
    {
        return new self("Guest session not found or expired.");
    }
}
