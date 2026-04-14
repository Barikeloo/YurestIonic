<?php

namespace App\Sale\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Sale\Domain\ValueObject\SaleLinePrice;
use App\Sale\Domain\ValueObject\SaleLineQuantity;
use App\Sale\Domain\ValueObject\SaleLineTaxPercentage;

final class SaleLine
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private readonly Uuid $saleId,
        private readonly Uuid $orderLineId,
        private readonly Uuid $productId,
        private readonly Uuid $userId,
        private readonly SaleLineQuantity $quantity,
        private readonly SaleLinePrice $price,
        private readonly SaleLineTaxPercentage $taxPercentage,
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
        Uuid $productId,
        Uuid $userId,
        SaleLineQuantity $quantity,
        SaleLinePrice $price,
        SaleLineTaxPercentage $taxPercentage,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            saleId: $saleId,
            orderLineId: $orderLineId,
            productId: $productId,
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
        Uuid $productId,
        Uuid $userId,
        SaleLineQuantity $quantity,
        SaleLinePrice $price,
        SaleLineTaxPercentage $taxPercentage,
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
            productId: $productId,
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

    public function getProductId(): Uuid
    {
        return $this->productId;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getQuantity(): SaleLineQuantity
    {
        return $this->quantity;
    }

    public function getPrice(): SaleLinePrice
    {
        return $this->price;
    }

    public function getTaxPercentage(): SaleLineTaxPercentage
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
