<?php

namespace App\Order\Domain\Entity;

use App\Order\Domain\ValueObject\OrderLineDinerNumber;
use App\Order\Domain\ValueObject\OrderLineDiscountAmount;
use App\Order\Domain\ValueObject\OrderLineDiscountPercent;
use App\Order\Domain\ValueObject\OrderLinePrice;
use App\Order\Domain\ValueObject\OrderLineQuantity;
use App\Order\Domain\ValueObject\OrderLineTaxPercentage;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class OrderLine
{
    /**
     * @param  array<int, array{id: string, name: string, price: int, type: string}>|null  $modifiers
     * @param  array<int, array{section_name: string, product_id: string, product_name: string, variant_id: ?string, variant_name: ?string, modifiers: array<int, array{id: string, name: string, price: int, type: string}>, extra_price: int}>|null  $menuSelections
     */
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $restaurantId,
        private readonly Uuid $uuid,
        private readonly Uuid $orderId,
        private readonly ?Uuid $productId,
        private readonly ?Uuid $variantId,
        private readonly ?string $variantName,
        private readonly ?array $modifiers,
        private readonly ?Uuid $menuId,
        private readonly ?string $menuName,
        private readonly ?array $menuSelections,
        private readonly Uuid $userId,
        private readonly OrderLineQuantity $quantity,
        private readonly OrderLinePrice $price,
        private readonly OrderLineTaxPercentage $taxPercentage,
        private readonly ?OrderLineDinerNumber $dinerNumber,
        private readonly ?OrderLineDiscountPercent $discountPercent,
        private readonly ?OrderLineDiscountAmount $discountAmount,
        private readonly ?string $discountReason,
        private readonly bool $isInvitation,
        private readonly ?OrderLinePrice $priceOverride,
        private readonly ?string $notes,
        private readonly DomainDateTime $createdAt,
        private readonly DomainDateTime $updatedAt,
        private readonly ?DomainDateTime $deletedAt = null,
    ) {}

    /**
     * @param  array<int, array{id: string, name: string, price: int, type: string}>|null  $modifiers
     */
    public static function dddCreate(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $orderId,
        Uuid $productId,
        ?Uuid $variantId,
        ?string $variantName,
        ?array $modifiers,
        Uuid $userId,
        OrderLineQuantity $quantity,
        OrderLinePrice $price,
        OrderLineTaxPercentage $taxPercentage,
        ?OrderLineDinerNumber $dinerNumber = null,
        ?OrderLineDiscountPercent $discountPercent = null,
        ?OrderLineDiscountAmount $discountAmount = null,
        ?string $discountReason = null,
        bool $isInvitation = false,
        ?OrderLinePrice $priceOverride = null,
        ?string $notes = null,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            orderId: $orderId,
            productId: $productId,
            variantId: $variantId,
            variantName: $variantName,
            modifiers: $modifiers,
            menuId: null,
            menuName: null,
            menuSelections: null,
            userId: $userId,
            quantity: $quantity,
            price: $price,
            taxPercentage: $taxPercentage,
            dinerNumber: $dinerNumber,
            discountPercent: $discountPercent,
            discountAmount: $discountAmount,
            discountReason: $discountReason,
            isInvitation: $isInvitation,
            priceOverride: $priceOverride,
            notes: $notes,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    /**
     * Crea una línea que representa la elección de un menú completo. No tiene
     * `productId`; el precio es el del menú y las elecciones del comensal viven
     * en `menuSelections` (cada item: sección + producto + variante? + extras + suplemento).
     *
     * @param  array<int, array{section_name: string, product_id: string, product_name: string, variant_id: ?string, variant_name: ?string, modifiers: array<int, array{id: string, name: string, price: int, type: string}>, extra_price: int}>  $menuSelections
     */
    public static function dddCreateMenuLine(
        Uuid $id,
        Uuid $restaurantId,
        Uuid $orderId,
        Uuid $menuId,
        string $menuName,
        array $menuSelections,
        Uuid $userId,
        OrderLineQuantity $quantity,
        OrderLinePrice $price,
        OrderLineTaxPercentage $taxPercentage,
        ?OrderLineDinerNumber $dinerNumber = null,
        ?string $notes = null,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            orderId: $orderId,
            productId: null,
            variantId: null,
            variantName: null,
            modifiers: null,
            menuId: $menuId,
            menuName: $menuName,
            menuSelections: $menuSelections,
            userId: $userId,
            quantity: $quantity,
            price: $price,
            taxPercentage: $taxPercentage,
            dinerNumber: $dinerNumber,
            discountPercent: null,
            discountAmount: null,
            discountReason: null,
            isInvitation: false,
            priceOverride: null,
            notes: $notes,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    /**
     * @param  array<int, array{id: string, name: string, price: int, type: string}>|null  $modifiers
     * @param  array<int, array{section_name: string, product_id: string, product_name: string, variant_id: ?string, variant_name: ?string, modifiers: array<int, array{id: string, name: string, price: int, type: string}>, extra_price: int}>|null  $menuSelections
     */
    public static function fromPersistence(
        string $id,
        string $restaurantId,
        string $uuid,
        string $orderId,
        ?string $productId,
        ?string $variantId,
        ?string $variantName,
        ?array $modifiers,
        ?string $menuId,
        ?string $menuName,
        ?array $menuSelections,
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
            productId: $productId !== null ? Uuid::create($productId) : null,
            variantId: $variantId !== null ? Uuid::create($variantId) : null,
            variantName: $variantName,
            modifiers: $modifiers,
            menuId: $menuId !== null ? Uuid::create($menuId) : null,
            menuName: $menuName,
            menuSelections: $menuSelections,
            userId: Uuid::create($userId),
            quantity: OrderLineQuantity::create($quantity),
            price: OrderLinePrice::create($price),
            taxPercentage: OrderLineTaxPercentage::create($taxPercentage),
            dinerNumber: $dinerNumber !== null ? OrderLineDinerNumber::create($dinerNumber) : null,
            discountPercent: $discountPercent !== null ? OrderLineDiscountPercent::create($discountPercent) : null,
            discountAmount: $discountAmountCents !== null ? OrderLineDiscountAmount::create($discountAmountCents) : null,
            discountReason: $discountReason,
            isInvitation: $isInvitation,
            priceOverride: $priceOverrideCents !== null ? OrderLinePrice::create($priceOverrideCents) : null,
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

    public function productId(): ?Uuid
    {
        return $this->productId;
    }

    public function menuId(): ?Uuid
    {
        return $this->menuId;
    }

    public function menuName(): ?string
    {
        return $this->menuName;
    }

    /**
     * @return array<int, array{section_name: string, product_id: string, product_name: string, variant_id: ?string, variant_name: ?string, modifiers: array<int, array{id: string, name: string, price: int, type: string}>, extra_price: int}>|null
     */
    public function menuSelections(): ?array
    {
        return $this->menuSelections;
    }

    public function isMenuLine(): bool
    {
        return $this->menuId !== null;
    }

    public function variantId(): ?Uuid
    {
        return $this->variantId;
    }

    public function variantName(): ?string
    {
        return $this->variantName;
    }

    /**
     * @return array<int, array{id: string, name: string, price: int, type: string}>|null
     */
    public function modifiers(): ?array
    {
        return $this->modifiers;
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

    public function dinerNumber(): ?OrderLineDinerNumber
    {
        return $this->dinerNumber;
    }

    public function discountPercent(): ?OrderLineDiscountPercent
    {
        return $this->discountPercent;
    }

    public function discountAmount(): ?OrderLineDiscountAmount
    {
        return $this->discountAmount;
    }

    public function discountReason(): ?string
    {
        return $this->discountReason;
    }

    public function isInvitation(): bool
    {
        return $this->isInvitation;
    }

    public function priceOverride(): ?OrderLinePrice
    {
        return $this->priceOverride;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    /**
     * Clona la línea apuntando a otra orden (caso de uso: merge de mesas).
     * Preserva si es línea de producto o de menú.
     */
    public function clonedForOrder(Uuid $newId, Uuid $newOrderId): self
    {
        return new self(
            id: $newId,
            restaurantId: $this->restaurantId,
            uuid: $newId,
            orderId: $newOrderId,
            productId: $this->productId,
            variantId: $this->variantId,
            variantName: $this->variantName,
            modifiers: $this->modifiers,
            menuId: $this->menuId,
            menuName: $this->menuName,
            menuSelections: $this->menuSelections,
            userId: $this->userId,
            quantity: $this->quantity,
            price: $this->price,
            taxPercentage: $this->taxPercentage,
            dinerNumber: $this->dinerNumber,
            discountPercent: $this->discountPercent,
            discountAmount: $this->discountAmount,
            discountReason: $this->discountReason,
            isInvitation: $this->isInvitation,
            priceOverride: $this->priceOverride,
            notes: $this->notes,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
            deletedAt: null,
        );
    }

    public function withAddedQuantity(int $delta): self
    {
        return new self(
            id: $this->id,
            restaurantId: $this->restaurantId,
            uuid: $this->uuid,
            orderId: $this->orderId,
            productId: $this->productId,
            variantId: $this->variantId,
            variantName: $this->variantName,
            modifiers: $this->modifiers,
            menuId: $this->menuId,
            menuName: $this->menuName,
            menuSelections: $this->menuSelections,
            userId: $this->userId,
            quantity: OrderLineQuantity::create($this->quantity->value() + $delta),
            price: $this->price,
            taxPercentage: $this->taxPercentage,
            dinerNumber: $this->dinerNumber,
            discountPercent: $this->discountPercent,
            discountAmount: $this->discountAmount,
            discountReason: $this->discountReason,
            isInvitation: $this->isInvitation,
            priceOverride: $this->priceOverride,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: DomainDateTime::now(),
            deletedAt: $this->deletedAt,
        );
    }
}
