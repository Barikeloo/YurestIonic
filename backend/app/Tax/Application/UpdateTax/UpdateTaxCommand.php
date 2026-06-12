<?php

namespace App\Tax\Application\UpdateTax;

final readonly class UpdateTaxCommand
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?int $percentage = null,
    ) {}
}
