<?php

declare(strict_types=1);

namespace App\Order\Application\BatchAddLinesToOrder;

final class BatchAddLinesToOrderResponse
{

    public function __construct(
        public readonly array $productLines,
        public readonly array $menuLines,
    ) {}

    public function toArray(): array
    {
        return [
            'product_lines' => $this->productLines,
            'menu_lines' => $this->menuLines,
        ];
    }
}
