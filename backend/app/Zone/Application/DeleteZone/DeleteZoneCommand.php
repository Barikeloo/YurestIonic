<?php

namespace App\Zone\Application\DeleteZone;

final readonly class DeleteZoneCommand
{
    public function __construct(
        public string $id,
    ) {}
}
