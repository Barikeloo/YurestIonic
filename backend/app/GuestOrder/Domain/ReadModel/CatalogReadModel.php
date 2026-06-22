<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ReadModel;

final readonly class CatalogReadModel
{
    public function __construct(
        public int $version,
        public array $families,
        public array $menus,
    ) {}
}
