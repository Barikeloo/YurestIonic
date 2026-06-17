<?php

declare(strict_types=1);

namespace App\Printer\Domain\Exception;

final class PrinterConnectionException extends \RuntimeException
{
    public static function cannotConnect(string $ip, int $port, string $reason): self
    {
        return new self("Cannot connect to printer at {$ip}:{$port} — {$reason}");
    }
}
