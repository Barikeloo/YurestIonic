<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateChargeSessionDiners;

final readonly class UpdateChargeSessionDinersCommand
{
    public function __construct(
        public string $chargeSessionId,
        public int $newDinersCount,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
