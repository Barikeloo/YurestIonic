<?php

declare(strict_types=1);

namespace App\Cash\Application\ForceCloseCashSession;

final readonly class ForceCloseCashSessionCommand
{
    public function __construct(
        public string $cashSessionId,
        public string $closedByUserId,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
