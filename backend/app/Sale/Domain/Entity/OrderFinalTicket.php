<?php

declare(strict_types=1);

namespace App\Sale\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class OrderFinalTicket
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $orderId,
        private readonly Uuid $closedByUserId,
        private int $ticketNumber,
        private int $totalConsumedCents,
        private int $totalPaidCents,
        private array $paymentsSnapshot,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt = null,
    ) {}

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $orderId,
        Uuid $closedByUserId,
        int $ticketNumber,
        int $totalConsumedCents,
        int $totalPaidCents,
        array $paymentsSnapshot,
    ): self {
        if ($ticketNumber <= 0) {
            throw new \DomainException('Ticket number must be greater than 0');
        }

        if ($totalConsumedCents < 0) {
            throw new \DomainException('Total consumed must be zero or greater');
        }

        if ($totalPaidCents < 0) {
            throw new \DomainException('Total paid must be zero or greater');
        }

        return new self(
            id: $id,
            restaurantId: $restaurantId,
            orderId: $orderId,
            closedByUserId: $closedByUserId,
            ticketNumber: $ticketNumber,
            totalConsumedCents: $totalConsumedCents,
            totalPaidCents: $totalPaidCents,
            paymentsSnapshot: $paymentsSnapshot,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $orderId,
        string $closedByUserId,
        int $ticketNumber,
        int $totalConsumedCents,
        int $totalPaidCents,
        array $paymentsSnapshot,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt = null,
    ): self {
        return new self(
            id: Uuid::create($id),
            restaurantId: Uuid::create($restaurantId),
            orderId: Uuid::create($orderId),
            closedByUserId: Uuid::create($closedByUserId),
            ticketNumber: $ticketNumber,
            totalConsumedCents: $totalConsumedCents,
            totalPaidCents: $totalPaidCents,
            paymentsSnapshot: $paymentsSnapshot,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
            deletedAt: $deletedAt !== null ? DomainDateTime::create($deletedAt) : null,
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function restaurantId(): Uuid
    {
        return $this->restaurantId;
    }

    public function orderId(): Uuid
    {
        return $this->orderId;
    }

    public function closedByUserId(): Uuid
    {
        return $this->closedByUserId;
    }

    public function ticketNumber(): int
    {
        return $this->ticketNumber;
    }

    public function totalConsumedCents(): int
    {
        return $this->totalConsumedCents;
    }

    public function totalPaidCents(): int
    {
        return $this->totalPaidCents;
    }

    public function paymentsSnapshot(): array
    {
        return $this->paymentsSnapshot;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DomainDateTime
    {
        return $this->deletedAt;
    }
}
