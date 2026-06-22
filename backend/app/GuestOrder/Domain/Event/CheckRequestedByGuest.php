<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final readonly class CheckRequestedByGuest implements DomainEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $guestSessionId,
        private string $orderId,
        private string $restaurantId,
        private string $tableId,
        private ?string $guestName,
        private string $requestedAt,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable { return $this->occurredOn; }
    public function guestSessionId(): string { return $this->guestSessionId; }
    public function orderId(): string { return $this->orderId; }
    public function restaurantId(): string { return $this->restaurantId; }
    public function tableId(): string { return $this->tableId; }
    public function guestName(): ?string { return $this->guestName; }
    public function requestedAt(): string { return $this->requestedAt; }
}
