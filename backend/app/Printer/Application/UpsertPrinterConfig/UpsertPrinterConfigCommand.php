<?php

declare(strict_types=1);

namespace App\Printer\Application\UpsertPrinterConfig;

final readonly class UpsertPrinterConfigCommand
{
    public function __construct(
        public int     $restaurantId,
        public ?string $uuid,
        public string  $name,
        public string  $ip,
        public int     $port,
        public int     $paperWidth,
        public bool    $enabled,
        public bool    $isDefault,
        public ?string $zoneUuid,
    ) {}
}
