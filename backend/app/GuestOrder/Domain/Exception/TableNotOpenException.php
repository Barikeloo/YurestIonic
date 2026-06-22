<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Exception;

final class TableNotOpenException extends \DomainException
{
    public static function forToken(string $token): self
    {
        return new self("Table for QR token '{$token}' has no active order to join.");
    }
}
