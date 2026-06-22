<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Interfaces;

use App\GuestOrder\Domain\Entity\GuestSession;

interface GuestSessionRepositoryInterface
{
    public function save(GuestSession $session): void;

    public function findBySessionToken(string $sessionToken): ?GuestSession;
}
