<?php

declare(strict_types=1);

namespace App\Order\Application\UpdateOrder;

final readonly class UpdateOrderCommand
{
    public function __construct(
        public string $id,
        public ?int $diners,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
