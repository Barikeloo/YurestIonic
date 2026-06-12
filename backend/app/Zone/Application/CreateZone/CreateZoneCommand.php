<?php

namespace App\Zone\Application\CreateZone;

final readonly class CreateZoneCommand
{
    public function __construct(
        public string $name,
    ) {}
}
