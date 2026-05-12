<?php

declare(strict_types=1);

namespace App\Cash\Application\OpenCashSession;

final readonly class OpenCashSessionCommand
{
    public function __construct(
        public string $restaurantId,
        public string $deviceId,
        public string $openedByUserId,
        public int $initialAmountCents,
        public ?string $notes,
    ) {}
}
