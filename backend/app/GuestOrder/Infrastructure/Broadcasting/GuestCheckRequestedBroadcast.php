<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final class GuestCheckRequestedBroadcast implements ShouldBroadcastNow
{
    public function __construct(
        private readonly string $restaurantId,
        public readonly string $orderId,
        public readonly string $tableId,
        public readonly ?string $guestName,
        public readonly string $requestedAt,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("restaurant.{$this->restaurantId}");
    }

    public function broadcastAs(): string
    {
        return 'guest.check_requested';
    }
}
