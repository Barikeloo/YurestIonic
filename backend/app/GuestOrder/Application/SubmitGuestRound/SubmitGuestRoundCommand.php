<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\SubmitGuestRound;

final readonly class SubmitGuestRoundCommand
{
    public function __construct(
        public string $token,
        public string $sessionToken,
        public array $lineIds,
        public string $idempotencyKey,
        public ?string $roundLabel,
    ) {}
}
