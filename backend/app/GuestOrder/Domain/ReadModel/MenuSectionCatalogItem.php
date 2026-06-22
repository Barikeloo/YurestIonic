<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ReadModel;

final readonly class MenuSectionCatalogItem
{
    public function __construct(
        public string $id,
        public string $name,
        public int $min_choices,
        public int $max_choices,
        public int $position,
        public array $items,
    ) {}
}
