<?php

namespace App\Sale\Domain\Entity;

use App\Sale\Domain\ValueObject\SaleLinePrice;
use App\Sale\Domain\ValueObject\SaleLineQuantity;
use App\Sale\Domain\ValueObject\SaleLineTaxPercentage;
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
        private readonly Uuid $productId,
        private readonly Uuid $userId,
        private readonly SaleLineQuantity $quantity,
        private readonly SaleLinePrice $price,
        private readonly SaleLineTaxPercentage $taxPercentage,
        private readonly DomainDateTime $createdAt,
        private readonly DomainDateTime $updatedAt,
        private readonly ?DomainDateTime $deletedAt = null,
    ) {}

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

    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $uuid,
        string $saleId,
        string $orderLineId,
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
            saleId: Uuid::create($saleId),
            orderLineId: Uuid::create($orderLineId),
            productId: Uuid::create($productId),
            userId: Uuid::create($userId),
            quantity: SaleLineQuantity::create($quantity),
            price: SaleLinePrice::create($price),
            taxPercentage: SaleLineTaxPercentage::create($taxPercentage),
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

    public function saleId(): Uuid
    {
        return $this->saleId;
    }

    public function orderLineId(): Uuid
    {
        return $this->orderLineId;
    }

    public function productId(): Uuid
    {
        return $this->productId;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function quantity(): SaleLineQuantity
    {
        return $this->quantity;
    }

    public function price(): SaleLinePrice
    {
        return $this->price;
    }

    public function taxPercentage(): SaleLineTaxPercentage
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
