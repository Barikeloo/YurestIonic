<?php

declare(strict_types=1);

namespace App\Printer\Application\TestPrinterConfig;

final readonly class TestPrinterConfigCommand
{
    public function __construct(
        public string $uuid,
    ) {}
}
