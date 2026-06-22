<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\OpenTableByGuest;

final readonly class OpenTableByGuestCommand
{
    public function __construct(
        public string $token,
        public string $sessionToken,
        public int $dinersCount,
        public string $identityMode,
        public ?string $guestName,
    ) {}
}
