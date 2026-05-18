<?php

declare(strict_types=1);

namespace App\Sale\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class ChargeSessionLineAssignment
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $chargeSessionId,
        private readonly Uuid $orderLineId,
        private readonly int $dinerNumber,
        private readonly DomainDateTime $createdAt,
        private readonly DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $id,
        Uuid $chargeSessionId,
        Uuid $orderLineId,
        int $dinerNumber,
    ): self {
        if ($dinerNumber < 1) {
            throw new \DomainException('Diner number must be >= 1');
        }

        return new self(
            id: $id,
            chargeSessionId: $chargeSessionId,
            orderLineId: $orderLineId,
            dinerNumber: $dinerNumber,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    public static function fromPersistence(
        string $id,
        string $chargeSessionId,
        string $orderLineId,
        int $dinerNumber,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            chargeSessionId: Uuid::create($chargeSessionId),
            orderLineId: Uuid::create($orderLineId),
            dinerNumber: $dinerNumber,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function chargeSessionId(): Uuid
    {
        return $this->chargeSessionId;
    }

    public function orderLineId(): Uuid
    {
        return $this->orderLineId;
    }

    public function dinerNumber(): int
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
}
