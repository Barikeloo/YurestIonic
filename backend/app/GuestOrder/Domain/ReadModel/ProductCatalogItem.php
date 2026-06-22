<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ReadModel;

final readonly class ProductCatalogItem
{
    public function __construct(
        public string $id,
        public string $name,
        public int $price_cents,
        public ?string $photo_url,
        public array $allergens,
        public bool $available,
        public array $variants,
        public array $modifiers,
    ) {}
}
