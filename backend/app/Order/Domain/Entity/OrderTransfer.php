<?php

namespace App\Order\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class OrderTransfer
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $orderId,
        private readonly Uuid $fromTableId,
        private readonly Uuid $toTableId,
        private readonly Uuid $transferredByUserId,
        private readonly DomainDateTime $transferredAt,
        private readonly DomainDateTime $createdAt,
        private readonly DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $id,
        Uuid $orderId,
        Uuid $fromTableId,
        Uuid $toTableId,
        Uuid $transferredByUserId,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            id: $id,
            orderId: $orderId,
            fromTableId: $fromTableId,
            toTableId: $toTableId,
            transferredByUserId: $transferredByUserId,
            transferredAt: $now,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $orderId,
        string $fromTableId,
        string $toTableId,
        string $transferredByUserId,
        \DateTimeImmutable $transferredAt,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            orderId: Uuid::create($orderId),
            fromTableId: Uuid::create($fromTableId),
            toTableId: Uuid::create($toTableId),
            transferredByUserId: Uuid::create($transferredByUserId),
            transferredAt: DomainDateTime::create($transferredAt),
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function orderId(): Uuid
    {
        return $this->orderId;
    }

    public function fromTableId(): Uuid
    {
        return $this->fromTableId;
    }

    public function toTableId(): Uuid
    {
        return $this->toTableId;
    }

    public function transferredByUserId(): Uuid
    {
        return $this->transferredByUserId;
    }

    public function transferredAt(): DomainDateTime
    {
        return $this->transferredAt;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }
}
