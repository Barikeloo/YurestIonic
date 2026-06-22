<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final class GuestOrderActivityBroadcast implements ShouldBroadcastNow
{
    public function __construct(
        private readonly string $orderId,
        public readonly string $eventType,
        public readonly ?string $guestName = null,
        public readonly ?int $roundNumber = null,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("guest-order.{$this->orderId}");
    }

    public function broadcastAs(): string
    {
        return 'guest.order_activity';
    }
}
