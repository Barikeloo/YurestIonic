<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Entity;

use App\GuestOrder\Domain\Event\GuestRoundSubmitted;
use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class GuestOrderRound
{
    use RecordsEvents;

    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $guestSessionId,
        private readonly Uuid $orderId,
        private readonly Uuid $restaurantId,
        private readonly int $roundNumber,
        private readonly ?string $label,
        private readonly string $idempotencyKey,
        private readonly DomainDateTime $submittedAt,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $guestSessionId,
        Uuid $orderId,
        Uuid $restaurantId,
        int $roundNumber,
        ?string $label,
        string $idempotencyKey,
        string $tableId,
        ?string $guestName,
        array $lineUuids,
    ): self {
        $now = DomainDateTime::now();

        $round = new self(
            id: Uuid::generate(),
            guestSessionId: $guestSessionId,
            orderId: $orderId,
            restaurantId: $restaurantId,
            roundNumber: $roundNumber,
            label: $label,
            idempotencyKey: $idempotencyKey,
            submittedAt: $now,
            createdAt: $now,
            updatedAt: $now,
        );

        $round->recordEvent(new GuestRoundSubmitted(
            roundId: $round->id->value(),
            guestSessionId: $guestSessionId->value(),
            orderId: $orderId->value(),
            restaurantId: $restaurantId->value(),
            tableId: $tableId,
            guestName: $guestName,
            roundNumber: $roundNumber,
            label: $label,
            lineUuids: $lineUuids,
            submittedAt: $now->format(\DateTimeInterface::ATOM),
        ));

        return $round;
    }

    public static function fromPersistence(
        string $id,
        string $guestSessionId,
        string $orderId,
        string $restaurantId,
        int $roundNumber,
        ?string $label,
        string $idempotencyKey,
        \DateTimeImmutable $submittedAt,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            guestSessionId: Uuid::create($guestSessionId),
            orderId: Uuid::create($orderId),
            restaurantId: Uuid::create($restaurantId),
            roundNumber: $roundNumber,
            label: $label,
            idempotencyKey: $idempotencyKey,
            submittedAt: DomainDateTime::create($submittedAt),
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function id(): Uuid { return $this->id; }
    public function guestSessionId(): Uuid { return $this->guestSessionId; }
    public function orderId(): Uuid { return $this->orderId; }
    public function restaurantId(): Uuid { return $this->restaurantId; }
    public function roundNumber(): int { return $this->roundNumber; }
    public function label(): ?string { return $this->label; }
    public function idempotencyKey(): string { return $this->idempotencyKey; }
    public function submittedAt(): DomainDateTime { return $this->submittedAt; }
    public function createdAt(): DomainDateTime { return $this->createdAt; }
    public function updatedAt(): DomainDateTime { return $this->updatedAt; }
}
