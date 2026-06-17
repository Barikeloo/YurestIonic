<?php

declare(strict_types=1);

namespace App\Printer\Application\UpsertPrinterConfig;

final readonly class UpsertPrinterConfigResponse
{
    public function __construct(
        public string  $uuid,
        public string  $name,
        public string  $ip,
        public int     $port,
        public int     $paperWidth,
        public bool    $enabled,
        public bool    $isDefault,
        public ?string $zoneUuid,
    ) {}

    public function toArray(): array
    {
        return [
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'ip'          => $this->ip,
            'port'        => $this->port,
            'paper_width' => $this->paperWidth,
            'enabled'     => $this->enabled,
            'is_default'  => $this->isDefault,
            'zone_uuid'   => $this->zoneUuid,
        ];
    }
}
