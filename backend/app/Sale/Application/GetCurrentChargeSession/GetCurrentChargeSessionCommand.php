<?php

declare(strict_types=1);

namespace App\Sale\Application\GetCurrentChargeSession;

final readonly class GetCurrentChargeSessionCommand
{
    public function __construct(
        public string $orderId,
    ) {}
}
