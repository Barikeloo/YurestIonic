<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Printing;

use App\Printer\Domain\Exception\PrinterConnectionException;
use App\Printer\Domain\Interfaces\PrinterServiceInterface;

final class TcpEscPosPrinterService implements PrinterServiceInterface
{
    private const TIMEOUT_SECONDS = 3.0;

    public function send(string $ip, int $port, string $bytes): void
    {
        $socket = @fsockopen($ip, $port, $errno, $errstr, self::TIMEOUT_SECONDS);

        if ($socket === false) {
            throw PrinterConnectionException::cannotConnect($ip, $port, $errstr ?: "errno {$errno}");
        }

        try {
            fwrite($socket, $bytes);
        } finally {
            fclose($socket);
        }
    }
}
