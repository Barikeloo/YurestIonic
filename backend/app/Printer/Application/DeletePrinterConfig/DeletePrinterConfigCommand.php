<?php

declare(strict_types=1);

namespace App\Printer\Application\DeletePrinterConfig;

final readonly class DeletePrinterConfigCommand
{
    public function __construct(
        public string $uuid,
    ) {}
}
