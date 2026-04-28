<?php

declare(strict_types=1);

namespace App\Cash\Domain\Entity;

use App\Cash\Domain\ValueObject\CashSessionStatus;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Cash\Domain\ValueObject\ZReportHash;
use App\Cash\Domain\ValueObject\ZReportNumber;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class CashSession
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private readonly DeviceId $deviceId,
        private readonly Uuid $openedByUserId,
        private ?Uuid $closedByUserId,
        private readonly DomainDateTime $openedAt,
        private ?DomainDateTime $closedAt,
        private readonly Money $initialAmount,
        private ?Money $finalAmount,
        private ?Money $expectedAmount,
        private ?Money $discrepancy,
        private ?string $discrepancyReason,
        private ?ZReportNumber $zReportNumber,
        private ?ZReportHash $zReportHash,
        private ?string $notes,
        private CashSessionStatus $status,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt = null,
    ) {}

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        DeviceId $deviceId,
        Uuid $openedByUserId,
        Money $initialAmount,
        ?string $notes = null,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            deviceId: $deviceId,
            openedByUserId: $openedByUserId,
            closedByUserId: null,
            openedAt: DomainDateTime::now(),
            closedAt: null,
            initialAmount: $initialAmount,
            finalAmount: null,
            expectedAmount: null,
            discrepancy: null,
            discrepancyReason: null,
            zReportNumber: null,
            zReportHash: null,
            notes: $notes,
            status: CashSessionStatus::open(),
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $uuid,
        string $deviceId,
        string $openedByUserId,
        ?string $closedByUserId,
        \DateTimeImmutable $openedAt,
        ?\DateTimeImmutable $closedAt,
        int $initialAmountCents,
        ?int $finalAmountCents,
        ?int $expectedAmountCents,
        ?int $discrepancyCents,
        ?string $discrepancyReason,
        ?int $zReportNumber,
        ?string $zReportHash,
        ?string $notes,
        string $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt = null,
    ): self {
        return new self(
            id: Uuid::create($id),
            restaurantId: Uuid::create($restaurantId),
            uuid: Uuid::create($uuid),
            deviceId: DeviceId::create($deviceId),
            openedByUserId: Uuid::create($openedByUserId),
            closedByUserId: $closedByUserId !== null ? Uuid::create($closedByUserId) : null,
            openedAt: DomainDateTime::create($openedAt),
            closedAt: $closedAt !== null ? DomainDateTime::create($closedAt) : null,
            initialAmount: Money::create($initialAmountCents),
            finalAmount: $finalAmountCents !== null ? Money::create($finalAmountCents) : null,
            expectedAmount: $expectedAmountCents !== null ? Money::create($expectedAmountCents) : null,
            discrepancy: $discrepancyCents !== null ? Money::create($discrepancyCents) : null,
            discrepancyReason: $discrepancyReason,
            zReportNumber: $zReportNumber !== null ? ZReportNumber::create($zReportNumber) : null,
            zReportHash: $zReportHash !== null ? ZReportHash::create($zReportHash) : null,
            notes: $notes,
            status: CashSessionStatus::create($status),
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
            deletedAt: $deletedAt !== null ? DomainDateTime::create($deletedAt) : null,
        );
    }

    public function startClosing(): void
    {
        if (! $this->status->isOpen()) {
            throw new \DomainException('Only open sessions can start closing.');
        }

        $this->status = CashSessionStatus::closing();
        $this->updatedAt = DomainDateTime::now();
    }

    public function cancelClosing(): void
    {
        if (! $this->status->isClosing()) {
            throw new \DomainException('Only closing sessions can cancel closing.');
        }

        $this->status = CashSessionStatus::open();
        $this->updatedAt = DomainDateTime::now();
    }

    public function close(
        Uuid $closedByUserId,
        Money $finalAmount,
        Money $expectedAmount,
        Money $discrepancy,
        ?ZReportNumber $zReportNumber = null,
        ?ZReportHash $zReportHash = null,
        ?string $discrepancyReason = null,
    ): void {
        if (! $this->status->isClosing()) {
            throw new \DomainException('Only closing sessions can be closed.');
        }

        $this->closedByUserId = $closedByUserId;
        $this->closedAt = DomainDateTime::now();
        $this->finalAmount = $finalAmount;
        $this->expectedAmount = $expectedAmount;
        $this->discrepancy = $discrepancy;
        $this->discrepancyReason = $discrepancyReason;
        $this->zReportNumber = $zReportNumber;
        $this->zReportHash = $zReportHash;
        $this->status = CashSessionStatus::closed();
        $this->updatedAt = DomainDateTime::now();
    }

    public function forceClose(Uuid $closedByUserId): void
    {
        if ($this->status->isClosed() || $this->status->isAbandoned()) {
            throw new \DomainException('Session is already closed or abandoned.');
        }

        $this->closedByUserId = $closedByUserId;
        $this->closedAt = DomainDateTime::now();
        $this->status = CashSessionStatus::abandoned();
        $this->updatedAt = DomainDateTime::now();
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

    public function deviceId(): DeviceId
    {
        return $this->deviceId;
    }

    public function openedByUserId(): Uuid
    {
        return $this->openedByUserId;
    }

    public function closedByUserId(): ?Uuid
    {
        return $this->closedByUserId;
    }

    public function openedAt(): DomainDateTime
    {
        return $this->openedAt;
    }

    public function closedAt(): ?DomainDateTime
    {
        return $this->closedAt;
    }

    public function initialAmount(): Money
    {
        return $this->initialAmount;
    }

    public function finalAmount(): ?Money
    {
        return $this->finalAmount;
    }

    public function expectedAmount(): ?Money
    {
        return $this->expectedAmount;
    }

    public function discrepancy(): ?Money
    {
        return $this->discrepancy;
    }

    public function discrepancyReason(): ?string
    {
        return $this->discrepancyReason;
    }

    public function zReportNumber(): ?ZReportNumber
    {
        return $this->zReportNumber;
    }

    public function zReportHash(): ?ZReportHash
    {
        return $this->zReportHash;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function status(): CashSessionStatus
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

    public function deletedAt(): ?DomainDateTime
    {
        return $this->deletedAt;
    }
}
