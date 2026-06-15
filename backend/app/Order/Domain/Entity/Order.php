<?php

namespace App\Order\Domain\Entity;

use App\Order\Domain\Event\OrderCancelled;
use App\Order\Domain\Event\OrderCreated;
use App\Order\Domain\Event\OrderDeleted;
use App\Order\Domain\Event\OrderDinersUpdated;
use App\Order\Domain\Event\OrderMarkedToCharge;
use App\Order\Domain\Event\OrderReopened;
use App\Order\Domain\Event\OrderTransferred;
use App\Order\Domain\Exception\OrderNotTransferableException;
use App\Order\Domain\ValueObject\OrderDiners;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class Order
{
    use RecordsEvents;
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private OrderStatus $status,
        private Uuid $tableId,
        private readonly Uuid $openedByUserId,
        private ?Uuid $closedByUserId,
        private OrderDiners $diners,
        private readonly ?DomainDateTime $openedAt,
        private ?DomainDateTime $closedAt,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt = null,
    ) {}

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $tableId,
        Uuid $openedByUserId,
        OrderDiners $diners,
    ): self {
        $order = new self(
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

        $order->recordEvent(new OrderCreated(
            orderUuid: $id->value(),
            tableUuid: $tableId->value(),
            diners: $diners->value(),
            restaurantId: $restaurantId->value(),
        ));

        return $order;
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

        $this->recordEvent(new OrderMarkedToCharge(
            orderUuid: $this->uuid->value(),
            restaurantId: $this->restaurantId->value(),
        ));
    }

    public function close(Uuid $closedByUserId): void
    {
        if (! $this->status->isOpen() && ! $this->status->isToCharge()) {
            throw new \DomainException('Only open or to-charge orders can be closed.');
        }

        $this->status = OrderStatus::invoiced();
        $this->closedByUserId = $closedByUserId;
        $this->closedAt = DomainDateTime::now();
        $this->updatedAt = DomainDateTime::now();
    }

    public function cancel(Uuid $cancelledByUserId): void
    {
        if (! $this->status->isOpen()) {
            throw new \DomainException('Only open orders can be cancelled.');
        }

        $this->status = OrderStatus::cancelled();
        $this->closedByUserId = $cancelledByUserId;
        $this->closedAt = DomainDateTime::now();
        $this->updatedAt = DomainDateTime::now();

        $this->recordEvent(new OrderCancelled(
            orderUuid: $this->uuid->value(),
            restaurantId: $this->restaurantId->value(),
        ));
    }

    public function reopen(Uuid $reopenedByUserId): void
    {
        if ($this->status->isInvoiced()) {
            $this->status = OrderStatus::toCharge();
            $this->closedByUserId = $reopenedByUserId;
            $this->closedAt = null;
            $this->updatedAt = DomainDateTime::now();

            $this->recordEvent(new OrderReopened(
                orderUuid: $this->uuid->value(),
                restaurantId: $this->restaurantId->value(),
            ));

            return;
        }

        if ($this->status->isToCharge()) {
            $this->status = OrderStatus::open();
            $this->closedByUserId = null;
            $this->closedAt = null;
            $this->updatedAt = DomainDateTime::now();

            $this->recordEvent(new OrderReopened(
                orderUuid: $this->uuid->value(),
                restaurantId: $this->restaurantId->value(),
            ));

            return;
        }

        throw new \DomainException('Only invoiced or to-charge orders can be reopened.');
    }

    public function updateDiners(OrderDiners $diners): void
    {
        $beforeDiners = $this->diners->value();
        $this->diners = $diners;
        $this->updatedAt = DomainDateTime::now();

        $this->recordEvent(new OrderDinersUpdated(
            orderUuid: $this->uuid->value(),
            beforeDiners: $beforeDiners,
            afterDiners: $diners->value(),
        ));
    }

    public function transferTo(Uuid $newTableId): void
    {
        $fromTableId = $this->tableId->value();

        if (! $this->status->isOpen() && ! $this->status->isToCharge()) {
            throw OrderNotTransferableException::create();
        }

        $this->tableId = $newTableId;
        $this->updatedAt = DomainDateTime::now();

        $this->recordEvent(new OrderTransferred(
            orderUuid: $this->uuid->value(),
            fromTableId: $fromTableId,
            toTableId: $newTableId->value(),
            restaurantId: $this->restaurantId->value(),
        ));
    }

    public function delete(): void
    {
        $this->deletedAt = DomainDateTime::now();
        $this->updatedAt = DomainDateTime::now();

        $this->recordEvent(new OrderDeleted(
            orderUuid: $this->uuid->value(),
            before: [
                'status' => $this->status->value(),
                'diners' => $this->diners->value(),
                'table_id' => $this->tableId->value(),
            ],
            restaurantId: $this->restaurantId->value(),
        ));
    }

    public function snapshot(): array
    {
        return [
            'table_id' => $this->tableId->value(),
            'status' => $this->status->value(),
            'diners' => $this->diners->value(),
        ];
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
