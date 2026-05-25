<?php

declare(strict_types=1);

namespace App\Order\Application\CancelOrder;

final readonly class CancelOrderCommand
{
    public function __construct(
        public string $id,
        public string $cancelledByUserId,
    ) {}
}
