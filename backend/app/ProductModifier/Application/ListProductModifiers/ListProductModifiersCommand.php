<?php

namespace App\ProductModifier\Application\ListProductModifiers;

final readonly class ListProductModifiersCommand
{
    public function __construct(
        public string $productId,
    ) {}
}
