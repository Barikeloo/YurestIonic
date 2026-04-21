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

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $uuid,
        string $status,
        string $tableId,
        string $openedByUserId,
        ?string $closedByUserId,
        int $diners,
        ?\DateTimeImmutable $openedAt,
        ?\DateTimeImmutable $closedAt,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt = null,
    ): self {
        return new self(
            id: Uuid::create($id),
            restaurantId: Uuid::create($restaurantId),
            uuid: Uuid::create($uuid),
            status: OrderStatus::create($status),
            tableId: Uuid::create($tableId),
            openedByUserId: Uuid::create($openedByUserId),
            closedByUserId: $closedByUserId !== null ? Uuid::create($closedByUserId) : null,
            diners: OrderDiners::create($diners),
            openedAt: $openedAt !== null ? DomainDateTime::create($openedAt) : null,
            closedAt: $closedAt !== null ? DomainDateTime::create($closedAt) : null,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
            deletedAt: $deletedAt !== null ? DomainDateTime::create($deletedAt) : null,
        );
    }

    public function markToCharge(Uuid $closedByUserId): void
    {
        if (! $this->status->isOpen()) {
            throw new \DomainException('Only open orders can be marked as to-charge.');
        }

        $this->status = OrderStatus::toCharge();
        $this->closedByUserId = $closedByUserId;
        $this->updatedAt = DomainDateTime::now();
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

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function tableId(): Uuid
    {
        return $this->tableId;
    }

    public function openedByUserId(): Uuid
    {
        return $this->openedByUserId;
    }

    public function closedByUserId(): ?Uuid
    {
        return $this->closedByUserId;
    }

    public function diners(): OrderDiners
    {
        return $this->diners;
    }

    public function openedAt(): ?DomainDateTime
    {
        return $this->openedAt;
    }

    public function closedAt(): ?DomainDateTime
    {
        return $this->closedAt;
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
