<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ReadModel;

final readonly class ModifierCatalogItem
{
    public function __construct(
        public string $id,
        public string $name,
        public int $price_cents,
        public bool $is_required,
        public string $selection_type,
    ) {}
}
