<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetCatalogForGuest;

final readonly class GetCatalogForGuestCommand
{
    public function __construct(
        public string $token,
    ) {}
}
