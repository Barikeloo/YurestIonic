<?php

namespace App\ProductVariant\Application\DeleteProductVariant;

final readonly class DeleteProductVariantCommand
{
    public function __construct(
        public string $id,
    ) {}
}
