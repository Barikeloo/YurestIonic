<?php

namespace App\Zone\Application\UpdateZone;

final readonly class UpdateZoneCommand
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}
}
