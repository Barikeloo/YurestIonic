<?php

declare(strict_types=1);

namespace App\Cash\Application\CloseCashSession;

final readonly class CloseCashSessionCommand
{
    public function __construct(
        public string $cashSessionId,
        public string $closedByUserId,
        public int $finalAmountCents,
        public ?string $discrepancyReason,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
