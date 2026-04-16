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
}
