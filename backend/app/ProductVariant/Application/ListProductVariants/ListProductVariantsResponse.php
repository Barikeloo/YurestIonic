<?php

namespace App\ProductVariant\Application\ListProductVariants;

final readonly class ListProductVariantsResponse
{

    private function __construct(
        public array $variants,
    ) {}

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
