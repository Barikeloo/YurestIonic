<?php

declare(strict_types=1);

namespace App\Menu\Domain\Entity;

use App\Menu\Domain\ValueObject\MenuItemExtraPrice;
use App\Shared\Domain\ValueObject\Uuid;

class MenuItem
{
    private function __construct(
        private Uuid $id,
        private Uuid $sectionId,
        private Uuid $productId,
        private ?Uuid $variantId,
        private MenuItemExtraPrice $extraPrice,
        private int $position,
    ) {}

    public static function dddCreate(
        Uuid $sectionId,
        Uuid $productId,
        ?Uuid $variantId,
        MenuItemExtraPrice $extraPrice,
        int $position,
    ): self {
        return new self(
            id: Uuid::generate(),
            sectionId: $sectionId,
            productId: $productId,
            variantId: $variantId,
            extraPrice: $extraPrice,
            position: $position,
        );
    }

    public static function fromPersistence(
        string $id,
        string $sectionId,
        string $productId,
        ?string $variantId,
        int $extraPrice,
        int $position,
    ): self {
        return new self(
            id: Uuid::create($id),
            sectionId: Uuid::create($sectionId),
            productId: Uuid::create($productId),
            variantId: $variantId !== null ? Uuid::create($variantId) : null,
            extraPrice: MenuItemExtraPrice::create($extraPrice),
            position: $position,
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function sectionId(): Uuid
    {
        return $this->sectionId;
    }

    public function productId(): Uuid
    {
        return $this->productId;
    }

    public function variantId(): ?Uuid
    {
        return $this->variantId;
    }

    public function extraPrice(): MenuItemExtraPrice
    {
        return $this->extraPrice;
    }

    public function position(): int
    {
        return $this->position;
    }
}
