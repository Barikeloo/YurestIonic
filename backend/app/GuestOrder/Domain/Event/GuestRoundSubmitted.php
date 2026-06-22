<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

final readonly class GuestRoundSubmitted implements DomainEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $roundId,
        private string $guestSessionId,
        private string $orderId,
        private string $restaurantId,
        private string $tableId,
        private ?string $guestName,
        private int $roundNumber,
        private ?string $label,
        private array $lineUuids,
        private string $submittedAt,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable { return $this->occurredOn; }
    public function roundId(): string { return $this->roundId; }
    public function guestSessionId(): string { return $this->guestSessionId; }
    public function orderId(): string { return $this->orderId; }
    public function restaurantId(): string { return $this->restaurantId; }
    public function tableId(): string { return $this->tableId; }
    public function guestName(): ?string { return $this->guestName; }
    public function roundNumber(): int { return $this->roundNumber; }
    public function label(): ?string { return $this->label; }
    public function lineUuids(): array { return $this->lineUuids; }
    public function submittedAt(): string { return $this->submittedAt; }
}
