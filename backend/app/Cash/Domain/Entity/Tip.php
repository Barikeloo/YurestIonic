<?php

declare(strict_types=1);

namespace App\Cash\Domain\Entity;

use App\Cash\Domain\ValueObject\TipSource;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class Tip
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private readonly Uuid $saleId,
        private readonly Uuid $cashSessionId,
        private readonly Money $amount,
        private readonly TipSource $source,
        private ?Uuid $beneficiaryUserId,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt = null,
    ) {}

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $saleId,
        Uuid $cashSessionId,
        Money $amount,
        string $source,
        ?Uuid $beneficiaryUserId = null,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            saleId: $saleId,
            cashSessionId: $cashSessionId,
            amount: $amount,
            source: TipSource::create($source),
            beneficiaryUserId: $beneficiaryUserId,
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
        int $amountCents,
        string $source,
        ?string $beneficiaryUserId,
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
            amount: Money::create($amountCents),
            source: TipSource::create($source),
            beneficiaryUserId: $beneficiaryUserId !== null ? Uuid::create($beneficiaryUserId) : null,
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

    public function amount(): Money
    {
        return $this->amount;
    }

    public function source(): TipSource
    {
        return $this->source;
    }

    public function beneficiaryUserId(): ?Uuid
    {
        return $this->beneficiaryUserId;
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
