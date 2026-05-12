<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashMovements;

final readonly class ListCashMovementsCommand
{
    public function __construct(
        public string $cashSessionId,
    ) {}
}
