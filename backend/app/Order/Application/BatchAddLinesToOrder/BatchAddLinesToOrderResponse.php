<?php

declare(strict_types=1);

namespace App\Order\Application\BatchAddLinesToOrder;

final class BatchAddLinesToOrderResponse
{
    /**
     * @param  list<array{id: string, product_name: string, quantity: int, price: int, tax_percentage: int, merged: bool}>  $productLines
     * @param  list<array{id: string, menu_name: string, quantity: int, price: int, tax_percentage: int}>  $menuLines
     */
    public function __construct(
        public readonly array $productLines,
        public readonly array $menuLines,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'product_lines' => $this->productLines,
            'menu_lines' => $this->menuLines,
        ];
    }
}
