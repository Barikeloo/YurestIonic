<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ReadModel;

final readonly class MenuCatalogItem
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public int $price_cents,
        public array $sections,
    ) {}
}
