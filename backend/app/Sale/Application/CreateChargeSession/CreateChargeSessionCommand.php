<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateChargeSession;

final readonly class CreateChargeSessionCommand
{
    public function __construct(
        public string $restaurantId,
        public string $orderId,
        public string $openedByUserId,
        public ?int $dinersCount,
    ) {}
}
