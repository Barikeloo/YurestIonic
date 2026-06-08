<?php

declare(strict_types=1);

namespace App\Order\Application\AddMenuLineToOrder;

final readonly class AddMenuLineToOrderCommand
{

    public function __construct(
        public string $restaurantId,
        public string $orderId,
        public string $menuId,
        public string $userId,
        public ?int $dinerNumber,
        public array $selections,
        public ?string $notes = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
