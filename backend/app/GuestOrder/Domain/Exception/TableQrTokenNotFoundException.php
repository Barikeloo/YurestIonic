<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Exception;

final class TableQrTokenNotFoundException extends \DomainException
{
    public static function withToken(string $token): self
    {
        return new self("QR token '{$token}' not found.");
    }

    public static function forTable(string $tableId): self
    {
        return new self("No QR token found for table '{$tableId}'.");
    }
}
