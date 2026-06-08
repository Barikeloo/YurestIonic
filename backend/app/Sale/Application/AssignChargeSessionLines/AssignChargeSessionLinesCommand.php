<?php

declare(strict_types=1);

namespace App\Sale\Application\AssignChargeSessionLines;

final readonly class AssignChargeSessionLinesCommand
{

    public function __construct(
        public string $chargeSessionId,
        public array $assignments,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
