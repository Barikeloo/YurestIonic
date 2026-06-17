<?php

declare(strict_types=1);

namespace App\Printer\Domain\Exception;

final class PrinterConfigNotFoundException extends \RuntimeException
{
    public static function forZone(string $zoneId): self
    {
        return new self("No printer configured for zone '{$zoneId}' and no default printer found.");
    }

    public static function withUuid(string $uuid): self
    {
        return new self("Printer config '{$uuid}' not found.");
    }
}
