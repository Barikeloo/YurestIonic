<?php

declare(strict_types=1);

namespace App\Cash\Application\GenerateZReport;

final readonly class GenerateZReportCommand
{
    public function __construct(
        public string $cashSessionId,
        public ?int $finalAmountCents = null,
    ) {}
}
