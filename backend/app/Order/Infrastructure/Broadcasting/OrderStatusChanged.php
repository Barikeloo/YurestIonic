<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final class OrderStatusChanged implements ShouldBroadcastNow
{
    public function __construct(
        private readonly string $restaurantId,
        public readonly string $eventType,
        public readonly string $orderId,
        public readonly ?string $tableId = null,
        public readonly ?string $fromTableId = null,
        public readonly ?string $toTableId = null,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("restaurant.{$this->restaurantId}");
    }

    public function broadcastAs(): string
    {
        return 'order.status_changed';
    }
}
