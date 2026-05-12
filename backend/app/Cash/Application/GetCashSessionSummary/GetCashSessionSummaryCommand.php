<?php

declare(strict_types=1);

namespace App\Cash\Application\GetCashSessionSummary;

final readonly class GetCashSessionSummaryCommand
{
    public function __construct(
        public string $cashSessionId,
    ) {}
}
