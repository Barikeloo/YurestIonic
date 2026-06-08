<?php

namespace App\ProductModifier\Application\ReorderProductModifiers;

final readonly class ReorderProductModifiersCommand
{

    public function __construct(
        public string $productId,
        public array $items,
    ) {}
}
