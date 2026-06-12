<?php

namespace App\Family\Application\UpdateFamily;

final readonly class UpdateFamilyCommand
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}
}
