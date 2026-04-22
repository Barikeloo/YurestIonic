<?php

namespace App\Sale\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Sale\Domain\ValueObject\SaleTicketNumber;
use App\Sale\Domain\ValueObject\SaleTotal;

final class Sale
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private readonly Uuid $orderId,
        private readonly Uuid $openedByUserId,
        private ?Uuid $closedByUserId,
        private ?SaleTicketNumber $ticketNumber,
        private readonly DomainDateTime $valueDate,
        private SaleTotal $total,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt = null,
        private ?Uuid $cancelledByUserId = null,
        private ?string $cancellationReason = null,
        private string $status = 'completed',
    ) {
    }

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $orderId,
        Uuid $openedByUserId,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            orderId: $orderId,
            openedByUserId: $openedByUserId,
            closedByUserId: null,
            ticketNumber: null,
            valueDate: DomainDateTime::now(),
            total: SaleTotal::create(0),
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
            cancelledByUserId: null,
            cancellationReason: null,
            status: 'closed',
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $uuid,
        string $orderId,
        string $openedByUserId,
        ?string $closedByUserId,
        ?int $ticketNumber,
        \DateTimeImmutable $valueDate,
        int $total,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt = null,
        ?string $cancelledByUserId = null,
        ?string $cancellationReason = null,
        string $status = 'completed',
    ): self {
        return new self(
            id: Uuid::create($id),
            restaurantId: Uuid::create($restaurantId),
            uuid: Uuid::create($uuid),
            orderId: Uuid::create($orderId),
            openedByUserId: Uuid::create($openedByUserId),
            closedByUserId: $closedByUserId !== null ? Uuid::create($closedByUserId) : null,
            ticketNumber: $ticketNumber !== null ? SaleTicketNumber::create($ticketNumber) : null,
            valueDate: DomainDateTime::create($valueDate),
            total: SaleTotal::create($total),
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
            deletedAt: $deletedAt !== null ? DomainDateTime::create($deletedAt) : null,
            cancelledByUserId: $cancelledByUserId !== null ? Uuid::create($cancelledByUserId) : null,
            cancellationReason: $cancellationReason,
            status: $status,
        );
    }

    public function close(Uuid $closedByUserId, SaleTicketNumber $ticketNumber, SaleTotal $total): void
    {
        $this->closedByUserId = $closedByUserId;
        $this->ticketNumber = $ticketNumber;
        $this->total = $total;
        $this->status = 'closed';
        $this->updatedAt = DomainDateTime::now();
    }

    public function cancel(Uuid $cancelledByUserId, string $reason): void
    {
        if ($this->status === 'cancelled') {
            throw new \DomainException('Sale is already cancelled.');
        }

        $this->cancelledByUserId = $cancelledByUserId;
        $this->cancellationReason = $reason;
        $this->status = 'cancelled';
        $this->updatedAt = DomainDateTime::now();
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function restaurantId(): Uuid
    {
        return $this->restaurantId;
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function orderId(): Uuid
    {
        return $this->orderId;
    }

    public function openedByUserId(): Uuid
    {
        return $this->openedByUserId;
    }

    public function closedByUserId(): ?Uuid
    {
        return $this->closedByUserId;
    }

    public function ticketNumber(): ?SaleTicketNumber
    {
        return $this->ticketNumber;
    }

    public function valueDate(): DomainDateTime
    {
        return $this->valueDate;
    }

    public function total(): SaleTotal
    {
        return $this->total;
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

    public function status(): string
    {
        return $this->status;
    }

    public function cancelledByUserId(): ?Uuid
    {
        return $this->cancelledByUserId;
    }

    public function cancellationReason(): ?string
    {
        return $this->cancellationReason;
    }
}
