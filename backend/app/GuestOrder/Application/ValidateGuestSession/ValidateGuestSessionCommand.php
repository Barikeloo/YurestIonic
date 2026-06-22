<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\ValidateGuestSession;

final readonly class ValidateGuestSessionCommand
{
    public function __construct(
        public string $token,
        public string $sessionToken,
    ) {}
}
