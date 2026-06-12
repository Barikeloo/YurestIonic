<?php

namespace App\Tax\Application\DeleteTax;

final readonly class DeleteTaxCommand
{
    public function __construct(
        public string $id,
    ) {}
}
