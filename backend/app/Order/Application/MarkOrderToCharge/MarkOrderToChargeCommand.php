<?php

declare(strict_types=1);

namespace App\Order\Application\MarkOrderToCharge;

final readonly class MarkOrderToChargeCommand
{
    public function __construct(
        public string $id,
        public string $closedByUserId,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
