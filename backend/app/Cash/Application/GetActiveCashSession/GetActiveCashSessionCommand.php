<?php

declare(strict_types=1);

namespace App\Cash\Application\GetActiveCashSession;

final readonly class GetActiveCashSessionCommand
{
    public function __construct(
        public string $restaurantId,
        public string $deviceId,
    ) {}
}
