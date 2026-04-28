<?php

declare(strict_types=1);

namespace App\Sale\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class ChargeSessionPayment
{
    private const STATUS_COMPLETED = 'completed';

    private const STATUS_CANCELLED = 'cancelled';

    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $chargeSessionId,
        private readonly int $dinerNumber,
        private readonly int $amount,
        private readonly string $paymentMethod,
        private string $status,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function create(
        Uuid $id,
        Uuid $chargeSessionId,
        int $dinerNumber,
        int $amount,
        string $paymentMethod,
    ): self {
        if ($dinerNumber < 1) {
            throw new \InvalidArgumentException('Diner number must be positive');
        }

        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }

        return new self(
            id: $id,
            chargeSessionId: $chargeSessionId,
            dinerNumber: $dinerNumber,
            amount: $amount,
            paymentMethod: $paymentMethod,
            status: self::STATUS_COMPLETED,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    public static function fromPersistence(
        string $id,
        string $chargeSessionId,
        int $dinerNumber,
        int $amount,
        string $paymentMethod,
        string $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            chargeSessionId: Uuid::create($chargeSessionId),
            dinerNumber: $dinerNumber,
            amount: $amount,
            paymentMethod: $paymentMethod,
            status: $status,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function cancel(): void
    {
        if ($this->status === self::STATUS_CANCELLED) {
            throw new \DomainException('Payment is already cancelled');
        }

        $this->status = self::STATUS_CANCELLED;
        $this->updatedAt = DomainDateTime::now();
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // Getters

    public function id(): Uuid
    {
        return $this->id;
    }

    public function chargeSessionId(): Uuid
    {
        return $this->chargeSessionId;
    }

    public function dinerNumber(): int
    {
        return $this->dinerNumber;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function paymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function status(): string
    {
        return $this->status;
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
