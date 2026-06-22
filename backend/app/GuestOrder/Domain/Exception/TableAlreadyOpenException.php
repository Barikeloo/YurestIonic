<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Exception;

final class TableAlreadyOpenException extends \DomainException
{
    public static function forToken(string $token): self
    {
        return new self("Table for QR token '{$token}' already has an active order.");
    }
}
