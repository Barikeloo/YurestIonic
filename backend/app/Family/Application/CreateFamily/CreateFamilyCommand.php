<?php

namespace App\Family\Application\CreateFamily;

final readonly class CreateFamilyCommand
{
    public function __construct(
        public string $name,
        public ?string $color = null,
        public ?string $icon = null,
    ) {}
}
