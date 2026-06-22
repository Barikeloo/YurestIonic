<?php

declare(strict_types=1);

namespace App\Product\Application\UpdateProductAvailability;

final readonly class UpdateProductAvailabilityResponse
{
    private function __construct(
        public string $productId,
        public bool $available,
    ) {}

    public static function create(string $productId, bool $available): self
    {
        return new self(productId: $productId, available: $available);
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'available'  => $this->available,
        ];
    }
}
