<?php

declare(strict_types=1);

namespace App\Printer\Application\ListPrinterConfigs;

final readonly class ListPrinterConfigsCommand
{
    public function __construct(
        public int $restaurantId,
    ) {}
}
