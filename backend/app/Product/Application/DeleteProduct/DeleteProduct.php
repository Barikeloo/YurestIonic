<?php

namespace App\Product\Application\DeleteProduct;

use App\Product\Domain\Interfaces\ProductRepositoryInterface;

class DeleteProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(string $id): bool
    {
        return $this->productRepository->deleteById($id);
    }
}
