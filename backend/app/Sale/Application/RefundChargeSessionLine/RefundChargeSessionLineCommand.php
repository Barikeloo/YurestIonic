<?php

declare(strict_types=1);

namespace App\Sale\Application\RefundChargeSessionLine;

final readonly class RefundChargeSessionLineCommand
{
    public function __construct(
        public string $chargeSessionId,
        public string $orderLineId,
        public string $refundedByUserId,
        public ?string $reason = null,
    ) {}
}
