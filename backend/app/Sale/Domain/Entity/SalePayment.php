<?php

declare(strict_types=1);

namespace App\Sale\Domain\Entity;

use App\Sale\Domain\ValueObject\PaymentMethod;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class SalePayment
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private readonly Uuid $saleId,
        private readonly Uuid $cashSessionId,
        private readonly PaymentMethod $method,
        private readonly Money $amount,
        private ?array $metadata,
        private readonly Uuid $userId,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt = null,
    ) {
    }

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $saleId,
        Uuid $cashSessionId,
        PaymentMethod $method,
        Money $amount,
        Uuid $userId,
        ?array $metadata = null,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            saleId: $saleId,
            cashSessionId: $cashSessionId,
            method: $method,
            amount: $amount,
            metadata: $metadata,
            userId: $userId,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $uuid,
        string $saleId,
        string $cashSessionId,
        string $method,
        int $amountCents,
        ?array $metadata,
        string $userId,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt = null,
    ): self {
        return new self(
            id: Uuid::create($id),
            restaurantId: Uuid::create($restaurantId),
            uuid: Uuid::create($uuid),
            saleId: Uuid::create($saleId),
            cashSessionId: Uuid::create($cashSessionId),
            method: PaymentMethod::create($method),
            amount: Money::create($amountCents),
            metadata: $metadata,
            userId: Uuid::create($userId),
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
            deletedAt: $deletedAt !== null ? DomainDateTime::create($deletedAt) : null,
        );
    }

    // Getters

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

    public function saleId(): Uuid
    {
        return $this->saleId;
    }

    public function cashSessionId(): Uuid
    {
        return $this->cashSessionId;
    }

    public function method(): PaymentMethod
    {
        return $this->method;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function metadata(): ?array
    {
        return $this->metadata;
    }

    public function userId(): Uuid
    {
        return $this->userId;
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
