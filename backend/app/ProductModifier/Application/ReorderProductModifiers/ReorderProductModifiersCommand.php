<?php

namespace App\ProductModifier\Application\ReorderProductModifiers;

final readonly class ReorderProductModifiersCommand
{
    /**
     * @param  array<int, array{id: string, sort_order: int}>  $items
     */
    public function __construct(
        public string $productId,
        public array $items,
    ) {}
}
