<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashSessions;

final readonly class ListCashSessionsCommand
{
    public function __construct(
        public string $restaurantId,
    ) {}
}
