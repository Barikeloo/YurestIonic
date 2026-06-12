<?php

namespace App\Tax\Application\CreateTax;

final readonly class CreateTaxCommand
{
    public function __construct(
        public string $name,
        public int $percentage,
    ) {}
}
