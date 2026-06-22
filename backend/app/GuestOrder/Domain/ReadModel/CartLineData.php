<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ReadModel;

final readonly class CartLineData
{
    public function __construct(
        public string $id,
        public ?string $productId,
        public ?string $productName,
        public ?string $menuId,
        public ?string $menuName,
        public ?string $variantId,
        public ?string $variantName,
        public array $modifiers,
        public int $quantity,
        public int $unitPrice,
        public ?string $notes,
        public string $sendStatus,
        public ?string $roundId,
    ) {}
}
