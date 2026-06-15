<?php

declare(strict_types=1);

namespace App\Cash\Application\RegisterCashMovement;

final readonly class RegisterCashMovementCommand
{
    public function __construct(
        public string $restaurantId,
        public string $cashSessionId,
        public string $type,
        public string $reasonCode,
        public int $amountCents,
        public string $userId,
        public ?string $description,
    ) {}
}
