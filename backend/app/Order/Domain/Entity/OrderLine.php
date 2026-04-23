<?php

namespace App\Order\Domain\Entity;

use App\Order\Domain\ValueObject\OrderLinePrice;
use App\Order\Domain\ValueObject\OrderLineQuantity;
use App\Order\Domain\ValueObject\OrderLineTaxPercentage;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class OrderLine
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private readonly Uuid $orderId,
        private readonly Uuid $productId,
        private readonly Uuid $userId,
        private readonly OrderLineQuantity $quantity,
        private readonly OrderLinePrice $price,
        private readonly OrderLineTaxPercentage $taxPercentage,
        private readonly ?int $dinerNumber,
        private readonly ?int $discountPercent,
        private readonly ?int $discountAmountCents,
        private readonly ?string $discountReason,
        private readonly bool $isInvitation,
        private readonly ?int $priceOverrideCents,
        private readonly ?string $notes,
        private readonly DomainDateTime $createdAt,
        private readonly DomainDateTime $updatedAt,
        private readonly ?DomainDateTime $deletedAt = null,
    ) {
    }

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $orderId,
        Uuid $productId,
        Uuid $userId,
        OrderLineQuantity $quantity,
        OrderLinePrice $price,
        OrderLineTaxPercentage $taxPercentage,
        ?int $dinerNumber = null,
        ?int $discountPercent = null,
        ?int $discountAmountCents = null,
        ?string $discountReason = null,
        bool $isInvitation = false,
        ?int $priceOverrideCents = null,
        ?string $notes = null,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            orderId: $orderId,
            productId: $productId,
            userId: $userId,
            quantity: $quantity,
            price: $price,
            taxPercentage: $taxPercentage,
            dinerNumber: $dinerNumber,
            discountPercent: $discountPercent,
            discountAmountCents: $discountAmountCents,
            discountReason: $discountReason,
            isInvitation: $isInvitation,
            priceOverrideCents: $priceOverrideCents,
            notes: $notes,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $uuid,
        string $orderId,
        string $productId,
        string $userId,
        int $quantity,
        int $price,
        int $taxPercentage,
        ?int $dinerNumber,
        ?int $discountPercent,
        ?int $discountAmountCents,
        ?string $discountReason,
        bool $isInvitation,
        ?int $priceOverrideCents,
        ?string $notes,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt = null,
    ): self {
        return new self(
            id: Uuid::create($id),
            restaurantId: Uuid::create($restaurantId),
            uuid: Uuid::create($uuid),
            orderId: Uuid::create($orderId),
            productId: Uuid::create($productId),
            userId: Uuid::create($userId),
            quantity: OrderLineQuantity::create($quantity),
            price: OrderLinePrice::create($price),
            taxPercentage: OrderLineTaxPercentage::create($taxPercentage),
            dinerNumber: $dinerNumber,
            discountPercent: $discountPercent,
            discountAmountCents: $discountAmountCents,
            discountReason: $discountReason,
            isInvitation: $isInvitation,
            priceOverrideCents: $priceOverrideCents,
            notes: $notes,
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

    public function orderId(): Uuid
    {
        return $this->orderId;
    }

    public function productId(): Uuid
    {
        return $this->productId;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function quantity(): OrderLineQuantity
    {
        return $this->quantity;
    }

    public function price(): OrderLinePrice
    {
        return $this->price;
    }

    public function taxPercentage(): OrderLineTaxPercentage
    {
        return $this->taxPercentage;
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

    public function dinerNumber(): ?int
    {
        return $this->dinerNumber;
    }

    public function discountPercent(): ?int
    {
        return $this->discountPercent;
    }

    public function discountAmountCents(): ?int
    {
        return $this->discountAmountCents;
    }

    public function discountReason(): ?string
    {
        return $this->discountReason;
    }

    public function isInvitation(): bool
    {
        return $this->isInvitation;
    }

    public function priceOverrideCents(): ?int
    {
        return $this->priceOverrideCents;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function withAddedQuantity(int $delta): self
    {
        return new self(
            id: $this->id,
            restaurantId: $this->restaurantId,
            uuid: $this->uuid,
            orderId: $this->orderId,
            productId: $this->productId,
            userId: $this->userId,
            quantity: OrderLineQuantity::create($this->quantity->value() + $delta),
            price: $this->price,
            taxPercentage: $this->taxPercentage,
            dinerNumber: $this->dinerNumber,
            discountPercent: $this->discountPercent,
            discountAmountCents: $this->discountAmountCents,
            discountReason: $this->discountReason,
            isInvitation: $this->isInvitation,
            priceOverrideCents: $this->priceOverrideCents,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: DomainDateTime::now(),
            deletedAt: $this->deletedAt,
        );
    }
}
