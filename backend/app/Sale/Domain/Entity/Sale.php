<?php

namespace App\Sale\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class Sale
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private readonly Uuid $orderId,
        private readonly Uuid $openedByUserId,
        private ?Uuid $closedByUserId,
        private ?int $ticketNumber,
        private readonly DomainDateTime $valueDate,
        private int $total,
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
            total: 0,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    public static function hydrate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $uuid,
        Uuid $orderId,
        Uuid $openedByUserId,
        ?Uuid $closedByUserId,
        ?int $ticketNumber,
        DomainDateTime $valueDate,
        int $total,
        DomainDateTime $createdAt,
        DomainDateTime $updatedAt,
        ?DomainDateTime $deletedAt = null,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $uuid,
            orderId: $orderId,
            openedByUserId: $openedByUserId,
            closedByUserId: $closedByUserId,
            ticketNumber: $ticketNumber,
            valueDate: $valueDate,
            total: $total,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
        );
    }

    public function close(Uuid $closedByUserId, int $ticketNumber, int $total): void
    {
        $this->closedByUserId = $closedByUserId;
        $this->ticketNumber = $ticketNumber;
        $this->total = $total;
        $this->updatedAt = DomainDateTime::now();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRestaurantId(): Uuid
    {
        return $this->restaurantId;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    public function getOpenedByUserId(): Uuid
    {
        return $this->openedByUserId;
    }

    public function getClosedByUserId(): ?Uuid
    {
        return $this->closedByUserId;
    }

    public function getTicketNumber(): ?int
    {
        return $this->ticketNumber;
    }

    public function getValueDate(): DomainDateTime
    {
        return $this->valueDate;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getCreatedAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DomainDateTime
    {
        return $this->deletedAt;
    }
}
