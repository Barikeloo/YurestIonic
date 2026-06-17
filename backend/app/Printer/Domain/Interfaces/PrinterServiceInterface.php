<?php

declare(strict_types=1);

namespace App\Printer\Domain\Interfaces;

interface PrinterServiceInterface
{
    /**
     * Sends raw bytes to the printer via TCP socket.
     *
     * @throws \App\Printer\Domain\Exception\PrinterConnectionException
     */
    public function send(string $ip, int $port, string $bytes): void;
}
