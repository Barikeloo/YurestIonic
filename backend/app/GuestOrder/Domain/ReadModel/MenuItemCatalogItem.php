<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ReadModel;

final readonly class MenuItemCatalogItem
{
    public function __construct(
        public string $id,
        public string $product_id,
        public string $product_name,
        public ?string $variant_id,
        public ?string $variant_name,
        public int $extra_price_cents,
        public int $position,
    ) {}
}
