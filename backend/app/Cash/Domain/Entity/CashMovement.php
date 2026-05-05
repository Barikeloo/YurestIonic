<?php

declare(strict_types=1);

namespace App\Cash\Domain\Entity;

use App\Cash\Domain\ValueObject\MovementReasonCode;
use App\Cash\Domain\ValueObject\MovementType;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class CashMovement
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private readonly Uuid $cashSessionId,
        private readonly MovementType $type,
        private readonly MovementReasonCode $reasonCode,
        private readonly Money $amount,
        private ?string $description,
        private readonly Uuid $userId,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt = null,
    ) {}

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $cashSessionId,
        MovementType $type,
        MovementReasonCode $reasonCode,
        Money $amount,
        Uuid $userId,
        ?string $description = null,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            cashSessionId: $cashSessionId,
            type: $type,
            reasonCode: $reasonCode,
            amount: $amount,
            description: $description,
            userId: $userId,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $uuid,
        string $cashSessionId,
        string $type,
        string $reasonCode,
        int $amountCents,
        ?string $description,
        string $userId,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt = null,
    ): self {
        return new self(
            id: Uuid::create($id),
            restaurantId: Uuid::create($restaurantId),
            uuid: Uuid::create($uuid),
            cashSessionId: Uuid::create($cashSessionId),
            type: MovementType::create($type),
            reasonCode: MovementReasonCode::create($reasonCode),
            amount: Money::create($amountCents),
            description: $description,
            userId: Uuid::create($userId),
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

    public function cashSessionId(): Uuid
    {
        return $this->cashSessionId;
    }

    public function type(): MovementType
    {
        return $this->type;
    }

    public function reasonCode(): MovementReasonCode
    {
        return $this->reasonCode;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function description(): ?string
    {
        return $this->description;
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
