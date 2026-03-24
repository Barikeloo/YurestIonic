<?php

namespace App\Product\Application\GetProduct;

use App\Product\Domain\Interfaces\ProductRepositoryInterface;

class GetProduct
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(string $id): ?GetProductResponse
    {
        $product = $this->productRepository->findById($id);

        if ($product === null) {
            return null;
        }

        return GetProductResponse::create($product);
    }
}
