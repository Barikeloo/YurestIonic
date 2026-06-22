<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final readonly class GuestSessionStarted implements DomainEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $guestSessionId,
        private string $tableQrTokenId,
        private string $restaurantId,
        private string $orderId,
        private ?string $guestName,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable { return $this->occurredOn; }
    public function guestSessionId(): string { return $this->guestSessionId; }
    public function tableQrTokenId(): string { return $this->tableQrTokenId; }
    public function restaurantId(): string { return $this->restaurantId; }
    public function orderId(): string { return $this->orderId; }
    public function guestName(): ?string { return $this->guestName; }
}
