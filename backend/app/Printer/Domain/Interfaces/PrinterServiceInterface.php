<?php

declare(strict_types=1);

namespace App\Printer\Domain\Interfaces;

interface PrinterServiceInterface
{
    public function send(string $ip, int $port, string $bytes): void;
}
