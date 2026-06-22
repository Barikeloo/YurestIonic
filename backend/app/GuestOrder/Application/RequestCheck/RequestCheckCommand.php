<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\RequestCheck;

final readonly class RequestCheckCommand
{
    public function __construct(
        public string $token,
        public string $sessionToken,
    ) {}
}
