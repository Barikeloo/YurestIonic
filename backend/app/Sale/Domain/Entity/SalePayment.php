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
        private readonly ?Uuid $chargeSessionId,
        private readonly ?int $dinerNumber,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt = null,
    ) {}

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $saleId,
        Uuid $cashSessionId,
        PaymentMethod $method,
        Money $amount,
        Uuid $userId,
        ?array $metadata = null,
        ?Uuid $chargeSessionId = null,
        ?int $dinerNumber = null,
    ): self {
        if ($dinerNumber !== null && $dinerNumber < 1) {
            throw new \InvalidArgumentException('Diner number must be positive');
        }

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
            chargeSessionId: $chargeSessionId,
            dinerNumber: $dinerNumber,
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
        ?string $chargeSessionId = null,
        ?int $dinerNumber = null,
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
            chargeSessionId: $chargeSessionId !== null ? Uuid::create($chargeSessionId) : null,
            dinerNumber: $dinerNumber,
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

    public function chargeSessionId(): ?Uuid
    {
        return $this->chargeSessionId;
    }

    public function dinerNumber(): ?int
    {
        return $this->dinerNumber;
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
