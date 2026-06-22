<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\JoinGuestSession;

final readonly class JoinGuestSessionCommand
{
    public function __construct(
        public string $token,
        public string $sessionToken,
        public string $identityMode,
        public ?string $guestName,
        public ?string $customerAuthToken = null,
    ) {}
}
