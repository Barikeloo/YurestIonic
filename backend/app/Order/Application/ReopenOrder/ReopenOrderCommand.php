<?php

declare(strict_types=1);

namespace App\Order\Application\ReopenOrder;

final readonly class ReopenOrderCommand
{
    public function __construct(
        public string $id,
        public string $reopenedByUserId,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
