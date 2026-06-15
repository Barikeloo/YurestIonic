<?php

declare(strict_types=1);

namespace App\Sale\Application\CancelChargeSession;

final readonly class CancelChargeSessionCommand
{
    public function __construct(
        public string $chargeSessionId,
        public string $cancelledByUserId,
        public ?string $reason,
    ) {}
}
