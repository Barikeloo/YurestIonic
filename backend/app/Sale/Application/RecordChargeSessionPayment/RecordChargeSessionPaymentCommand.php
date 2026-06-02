<?php

declare(strict_types=1);

namespace App\Sale\Application\RecordChargeSessionPayment;

final readonly class RecordChargeSessionPaymentCommand
{
    public function __construct(
        public string $chargeSessionId,
        public string $paymentMethod,
        public string $openedByUserId,
        public string $closedByUserId,
        public string $deviceId,
        public ?int $dinerNumber,
        public ?int $amountCents,
        public ?string $ipAddress = null,
    ) {}
}
