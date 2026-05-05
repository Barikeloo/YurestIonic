<?php

declare(strict_types=1);

namespace App\Sale\Domain\Entity;

use App\Sale\Domain\ValueObject\ChargeSessionStatus;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class ChargeSession
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $orderId,
        private readonly Uuid $openedByUserId,
        private int $dinersCount,
        private readonly int $totalCents,
        private ChargeSessionStatus $status,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt = null,
        private ?Uuid $cancelledByUserId = null,
        private ?string $cancellationReason = null,
        private ?DomainDateTime $cancelledAt = null,
    ) {}

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $orderId,
        Uuid $openedByUserId,
        int $dinersCount,
        int $totalCents,
    ): self {
        if ($dinersCount <= 0) {
            throw new \DomainException('Diners count must be greater than 0');
        }

        if ($totalCents <= 0) {
            throw new \DomainException('Total must be greater than 0');
        }

        return new self(
            id: $id,
            restaurantId: $restaurantId,
            orderId: $orderId,
            openedByUserId: $openedByUserId,
            dinersCount: $dinersCount,
            totalCents: $totalCents,
            status: ChargeSessionStatus::active(),
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $orderId,
        string $openedByUserId,
        int $dinersCount,
        int $totalCents,
        string $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt = null,
        ?string $cancelledByUserId = null,
        ?string $cancellationReason = null,
        ?\DateTimeImmutable $cancelledAt = null,
    ): self {
        return new self(
            id: Uuid::create($id),
            restaurantId: Uuid::create($restaurantId),
            orderId: Uuid::create($orderId),
            openedByUserId: Uuid::create($openedByUserId),
            dinersCount: $dinersCount,
            totalCents: $totalCents,
            status: ChargeSessionStatus::create($status),
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
            deletedAt: $deletedAt !== null ? DomainDateTime::create($deletedAt) : null,
            cancelledByUserId: $cancelledByUserId !== null ? Uuid::create($cancelledByUserId) : null,
            cancellationReason: $cancellationReason,
            cancelledAt: $cancelledAt !== null ? DomainDateTime::create($cancelledAt) : null,
        );
    }

    public function updateDinersCount(int $newDinersCount, int $paidDinersCount = 0): void
    {
        if (! $this->status->isActive()) {
            throw new \DomainException('Cannot modify diners: session is not active');
        }

        if ($newDinersCount <= 0) {
            throw new \DomainException('Diners count must be greater than 0');
        }

        if ($newDinersCount < $paidDinersCount) {
            throw new \DomainException(
                "Cannot reduce diners below already-paid count ({$paidDinersCount})"
            );
        }

        $this->dinersCount = $newDinersCount;
        $this->updatedAt = DomainDateTime::now();
    }

    public function remainingAmount(int $paidCents): int
    {
        return max(0, $this->totalCents - $paidCents);
    }

    public function amountForNextDiner(int $paidCents, int $pendingDinersCount): int
    {
        if ($pendingDinersCount <= 0) {
            throw new \DomainException('No pending diners to charge');
        }

        $remaining = $this->remainingAmount($paidCents);

        if ($pendingDinersCount === 1) {
            return $remaining;
        }

        return (int) floor($remaining / $pendingDinersCount);
    }

    public function markCompleted(): void
    {
        if (! $this->status->isActive()) {
            return;
        }

        $this->status = ChargeSessionStatus::completed();
        $this->updatedAt = DomainDateTime::now();
    }

    public function cancel(Uuid $cancelledByUserId, ?string $reason = null): void
    {
        if (! $this->status->isActive()) {
            throw new \DomainException('Cannot cancel: session is not active');
        }

        $this->status = ChargeSessionStatus::cancelled();
        $this->cancelledByUserId = $cancelledByUserId;
        $this->cancellationReason = $reason;
        $this->cancelledAt = DomainDateTime::now();
        $this->updatedAt = DomainDateTime::now();
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

    public function openedByUserId(): Uuid
    {
        return $this->openedByUserId;
    }

    public function dinersCount(): int
    {
        return $this->dinersCount;
    }

    public function totalCents(): int
    {
        return $this->totalCents;
    }

    public function status(): ChargeSessionStatus
    {
        return $this->status;
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

    public function cancelledByUserId(): ?Uuid
    {
        return $this->cancelledByUserId;
    }

    public function cancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function cancelledAt(): ?DomainDateTime
    {
        return $this->cancelledAt;
    }
}
