<?php

declare(strict_types=1);

namespace App\Menu\Application\Shared;

final readonly class MenuItemInput
{
    public function __construct(
        public string $productId,
        public ?string $variantId,
        public int $extraPrice,
        public int $position,
    ) {}

    public static function fromArray(array $data, int $defaultPosition = 0): self
    {
        return new self(
            productId: (string) $data['product_id'],
            variantId: isset($data['variant_id']) && $data['variant_id'] !== '' ? (string) $data['variant_id'] : null,
            extraPrice: (int) ($data['extra_price'] ?? 0),
            position: (int) ($data['position'] ?? $defaultPosition),
        );
    }
}
