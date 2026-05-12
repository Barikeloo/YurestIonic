<?php

declare(strict_types=1);

namespace App\Cash\Application\GetLastClosedCashSession;

final readonly class GetLastClosedCashSessionCommand
{
    public function __construct(
        public string $restaurantId,
    ) {}
}
