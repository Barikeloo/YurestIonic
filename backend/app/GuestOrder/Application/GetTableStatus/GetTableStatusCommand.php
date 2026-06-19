<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetTableStatus;

final readonly class GetTableStatusCommand
{
    public function __construct(
        public string $token,
    ) {}
}
