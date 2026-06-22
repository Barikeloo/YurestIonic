<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ValueObject;

final readonly class GuestLineInput
{
    public function __construct(
        public ?string $productId,
        public ?string $menuId,
        public int $quantity,
        public ?string $variantId,
        public array $modifierIds,
        public ?string $notes,
        public array $menuSelections,
    ) {}

    public function isProductLine(): bool
    {
        return $this->productId !== null;
    }

    public function isMenuLine(): bool
    {
        return $this->menuId !== null;
    }
}
