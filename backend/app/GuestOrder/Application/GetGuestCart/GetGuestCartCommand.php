<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetGuestCart;

final readonly class GetGuestCartCommand
{
    public function __construct(
        public string $token,
        public string $sessionToken,
    ) {}
}
