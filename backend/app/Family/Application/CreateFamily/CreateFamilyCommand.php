<?php

namespace App\Family\Application\CreateFamily;

final readonly class CreateFamilyCommand
{
    public function __construct(
        public string $name,
    ) {}
}
