<?php

namespace App\ProductVariant\Application\ListProductVariants;

final readonly class ListProductVariantsResponse
{
    /**
     * @param array<int, array<string, mixed>> $variants
     */
    private function __construct(
        public array $variants,
    ) {}

    /**
     * @param array<int, array<string, mixed>> $variants
     */
    public static function create(array $variants): self
    {
        return new self(variants: $variants);
    }

    public function toArray(): array
    {
        return [
            'variants' => $this->variants,
        ];
    }
}
