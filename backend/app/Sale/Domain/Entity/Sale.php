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
        );
    }

    public function close(Uuid $closedByUserId, SaleTicketNumber $ticketNumber, SaleTotal $total): void
    {
        $this->closedByUserId = $closedByUserId;
        $this->ticketNumber = $ticketNumber;
        $this->total = $total;
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
}
