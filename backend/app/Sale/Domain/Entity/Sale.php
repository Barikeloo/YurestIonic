<?php

namespace App\Sale\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Sale\Domain\ValueObject\CustomerFiscalData;
use App\Sale\Domain\ValueObject\DocumentType;
use App\Sale\Domain\ValueObject\SaleStatus;
use App\Sale\Domain\ValueObject\SaleTicketNumber;
use App\Sale\Domain\ValueObject\SaleTotal;

final class Sale
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private readonly Uuid $orderId,
        private readonly Uuid $openedByUserId,
        private ?Uuid $closedByUserId,
        private ?SaleTicketNumber $ticketNumber,
        private readonly DomainDateTime $valueDate,
        private SaleTotal $total,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private SaleStatus $status,
        private DocumentType $documentType,
        private ?DomainDateTime $deletedAt = null,
        private ?Uuid $cashSessionId = null,
        private ?Uuid $cancelledByUserId = null,
        private ?string $cancellationReason = null,
        private ?DomainDateTime $cancelledAt = null,
        private ?Uuid $parentSaleId = null,
        private ?CustomerFiscalData $customerFiscalData = null,
    ) {
    }

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $orderId,
        Uuid $openedByUserId,
        ?Uuid $cashSessionId = null,
        ?Uuid $parentSaleId = null,
        ?DocumentType $documentType = null,
        ?CustomerFiscalData $customerFiscalData = null,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            orderId: $orderId,
            openedByUserId: $openedByUserId,
            closedByUserId: null,
            ticketNumber: null,
            valueDate: DomainDateTime::now(),
            total: SaleTotal::create(0),
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
            cashSessionId: $cashSessionId,
            status: SaleStatus::closed(),
            parentSaleId: $parentSaleId,
            documentType: $documentType ?? DocumentType::simplified(),
            customerFiscalData: $customerFiscalData,
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $uuid,
        string $orderId,
        string $openedByUserId,
        ?string $closedByUserId,
        ?int $ticketNumber,
        \DateTimeImmutable $valueDate,
        int $total,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt = null,
        ?string $cashSessionId = null,
        ?string $cancelledByUserId = null,
        ?string $cancellationReason = null,
        ?\DateTimeImmutable $cancelledAt = null,
        string $status = 'closed',
        ?string $parentSaleId = null,
        ?string $documentType = null,
        ?array $customerFiscalData = null,
    ): self {
        return new self(
            id: Uuid::create($id),
            restaurantId: Uuid::create($restaurantId),
            uuid: Uuid::create($uuid),
            orderId: Uuid::create($orderId),
            openedByUserId: Uuid::create($openedByUserId),
            closedByUserId: $closedByUserId !== null ? Uuid::create($closedByUserId) : null,
            ticketNumber: $ticketNumber !== null ? SaleTicketNumber::create($ticketNumber) : null,
            valueDate: DomainDateTime::create($valueDate),
            total: SaleTotal::create($total),
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
            deletedAt: $deletedAt !== null ? DomainDateTime::create($deletedAt) : null,
            cashSessionId: $cashSessionId !== null ? Uuid::create($cashSessionId) : null,
            cancelledByUserId: $cancelledByUserId !== null ? Uuid::create($cancelledByUserId) : null,
            cancellationReason: $cancellationReason,
            cancelledAt: $cancelledAt !== null ? DomainDateTime::create($cancelledAt) : null,
            status: SaleStatus::create($status),
            parentSaleId: $parentSaleId !== null ? Uuid::create($parentSaleId) : null,
            documentType: DocumentType::create($documentType ?? 'simplified'),
            customerFiscalData: $customerFiscalData !== null ? CustomerFiscalData::fromArray($customerFiscalData) : null,
        );
    }

    public function close(Uuid $closedByUserId, SaleTicketNumber $ticketNumber, SaleTotal $total): void
    {
        $this->closedByUserId = $closedByUserId;
        $this->ticketNumber = $ticketNumber;
        $this->total = $total;
        $this->status = SaleStatus::closed();
        $this->updatedAt = DomainDateTime::now();
    }

    public function cancel(Uuid $cancelledByUserId, string $reason): void
    {
        if ($this->status->isCancelled()) {
            throw new \DomainException('Sale is already cancelled.');
        }

        $this->cancelledByUserId = $cancelledByUserId;
        $this->cancellationReason = $reason;
        $this->cancelledAt = DomainDateTime::now();
        $this->status = SaleStatus::cancelled();
        $this->updatedAt = DomainDateTime::now();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
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

    public function orderId(): Uuid
    {
        return $this->orderId;
    }

    public function openedByUserId(): Uuid
    {
        return $this->openedByUserId;
    }

    public function closedByUserId(): ?Uuid
    {
        return $this->closedByUserId;
    }

    public function ticketNumber(): ?SaleTicketNumber
    {
        return $this->ticketNumber;
    }

    public function valueDate(): DomainDateTime
    {
        return $this->valueDate;
    }

    public function total(): SaleTotal
    {
        return $this->total;
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

    public function cashSessionId(): ?Uuid
    {
        return $this->cashSessionId;
    }

    public function status(): SaleStatus
    {
        return $this->status;
    }

    public function cancelledByUserId(): ?Uuid
    {
        return $this->cancelledByUserId;
    }

    public function cancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function cancelledAt(): ?DomainDateTime
    {
        return $this->cancelledAt;
    }

    public function parentSaleId(): ?Uuid
    {
        return $this->parentSaleId;
    }

    public function documentType(): DocumentType
    {
        return $this->documentType;
    }

    public function customerFiscalData(): ?CustomerFiscalData
    {
        return $this->customerFiscalData;
    }
}
