<?php

declare(strict_types=1);

namespace App\Cash\Application\CancelClosingCashSession;

final readonly class CancelClosingCashSessionCommand
{
    public function __construct(
        public string $cashSessionId,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
