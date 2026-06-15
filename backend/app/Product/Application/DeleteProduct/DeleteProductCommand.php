<?php

namespace App\Product\Application\DeleteProduct;

final readonly class DeleteProductCommand
{
    public function __construct(
        public string $id,
    ) {}
}
