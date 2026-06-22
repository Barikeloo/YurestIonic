<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ReadModel;

final readonly class FamilyCatalogItem
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $icon,
        public ?string $color,
        public array $products,
    ) {}
}
