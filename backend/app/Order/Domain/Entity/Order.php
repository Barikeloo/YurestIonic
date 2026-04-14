<?php

namespace App\Order\Domain\Entity;

use App\Order\Domain\ValueObject\OrderDiners;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class Order
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private OrderStatus $status,
        private readonly Uuid $tableId,
        private readonly Uuid $openedByUserId,
        private ?Uuid $closedByUserId,
        private OrderDiners $diners,
        private readonly DomainDateTime $openedAt,
        private ?DomainDateTime $closedAt,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt = null,
    ) {
    }

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $tableId,
        Uuid $openedByUserId,
        OrderDiners $diners,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            status: OrderStatus::open(),
            tableId: $tableId,
            openedByUserId: $openedByUserId,
            closedByUserId: null,
            diners: $diners,
            openedAt: DomainDateTime::now(),
            closedAt: null,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    public static function hydrate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $uuid,
        OrderStatus $status,
        Uuid $tableId,
        Uuid $openedByUserId,
        ?Uuid $closedByUserId,
        OrderDiners $diners,
        DomainDateTime $openedAt,
        ?DomainDateTime $closedAt,
        DomainDateTime $createdAt,
        DomainDateTime $updatedAt,
        ?DomainDateTime $deletedAt = null,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $uuid,
            status: $status,
            tableId: $tableId,
            openedByUserId: $openedByUserId,
            closedByUserId: $closedByUserId,
            diners: $diners,
            openedAt: $openedAt,
            closedAt: $closedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
        );
    }

    public function close(Uuid $closedByUserId): void
    {
        $this->status = OrderStatus::invoiced();
        $this->closedByUserId = $closedByUserId;
        $this->closedAt = DomainDateTime::now();
        $this->updatedAt = DomainDateTime::now();
    }

    public function cancel(Uuid $cancelledByUserId): void
    {
        $this->status = OrderStatus::cancelled();
        $this->closedByUserId = $cancelledByUserId;
        $this->closedAt = DomainDateTime::now();
        $this->updatedAt = DomainDateTime::now();
    }

    public function updateDiners(OrderDiners $diners): void
    {
        $this->diners = $diners;
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

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getTableId(): Uuid
    {
        return $this->tableId;
    }

    public function getOpenedByUserId(): Uuid
    {
        return $this->openedByUserId;
    }

    public function getClosedByUserId(): ?Uuid
    {
        return $this->closedByUserId;
    }

    public function getDiners(): OrderDiners
    {
        return $this->diners;
    }

    public function getOpenedAt(): DomainDateTime
    {
        return $this->openedAt;
    }

    public function getClosedAt(): ?DomainDateTime
    {
        return $this->closedAt;
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
