<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetCatalogVersion;

final readonly class GetCatalogVersionCommand
{
    public function __construct(
        public string $token,
    ) {}
}
