<?php

namespace App\ProductModifier\Application\DeleteProductModifier;

final readonly class DeleteProductModifierCommand
{
    public function __construct(
        public string $id,
    ) {}
}
