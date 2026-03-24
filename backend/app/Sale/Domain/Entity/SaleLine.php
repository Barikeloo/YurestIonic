<?php

namespace App\Sale\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class SaleLine
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private readonly Uuid $saleId,
        private readonly Uuid $orderLineId,
        private readonly Uuid $userId,
        private readonly int $quantity,
        private readonly int $price,
        private readonly int $taxPercentage,
        private readonly DomainDateTime $createdAt,
        private readonly DomainDateTime $updatedAt,
        private readonly ?DomainDateTime $deletedAt = null,
    ) {
    }

    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $saleId,
        Uuid $orderLineId,
        Uuid $userId,
        int $quantity,
        int $price,
        int $taxPercentage,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            saleId: $saleId,
            orderLineId: $orderLineId,
            userId: $userId,
            quantity: $quantity,
            price: $price,
            taxPercentage: $taxPercentage,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    public static function hydrate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $uuid,
        Uuid $saleId,
        Uuid $orderLineId,
        Uuid $userId,
        int $quantity,
        int $price,
        int $taxPercentage,
        DomainDateTime $createdAt,
        DomainDateTime $updatedAt,
        ?DomainDateTime $deletedAt = null,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $uuid,
            saleId: $saleId,
            orderLineId: $orderLineId,
            userId: $userId,
            quantity: $quantity,
            price: $price,
            taxPercentage: $taxPercentage,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRestaurantId(): Uuid
    {
        return $this->restaurantId;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getSaleId(): Uuid
    {
        return $this->saleId;
    }

    public function getOrderLineId(): Uuid
    {
        return $this->orderLineId;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getTaxPercentage(): int
    {
        return $this->taxPercentage;
    }

    public function getCreatedAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DomainDateTime
    {
        return $this->deletedAt;
    }
}
