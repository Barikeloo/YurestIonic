<?php

declare(strict_types=1);

namespace App\Cash\Application\GetZReport;

final readonly class GetZReportCommand
{
    public function __construct(
        public string $zReportId,
    ) {}
}
