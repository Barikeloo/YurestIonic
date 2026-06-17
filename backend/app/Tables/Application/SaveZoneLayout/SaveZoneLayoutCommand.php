<?php

declare(strict_types=1);

namespace App\Tables\Application\SaveZoneLayout;

final readonly class SaveZoneLayoutCommand
{
    /**
     * @param SaveZoneLayoutTableDto[] $tables
     */
    public function __construct(
        public string $zoneId,
        public array  $tables,
    ) {}
}
