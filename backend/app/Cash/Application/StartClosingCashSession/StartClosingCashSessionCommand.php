<?php

declare(strict_types=1);

namespace App\Cash\Application\StartClosingCashSession;

final readonly class StartClosingCashSessionCommand
{
    public function __construct(
        public string $cashSessionId,
    ) {}
}
