<?php

declare(strict_types=1);

namespace App\Tables\Infrastructure\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final class TableStatusChanged implements ShouldBroadcastNow
{
    public function __construct(
        private readonly string $restaurantId,
        public readonly string $eventType,
        public readonly string $groupId,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("restaurant.{$this->restaurantId}");
    }

    public function broadcastAs(): string
    {
        return 'table.status_changed';
    }
}
