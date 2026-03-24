<?php

namespace App\Product\Application\ListProducts;

use App\Product\Domain\Interfaces\ProductRepositoryInterface;

class ListProducts
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {}

    /**
     * @return array<int, array<string, bool|int|string|null>>
     */
    public function __invoke(): array
    {
        $products = $this->productRepository->findAll();

        return array_map(
            static fn ($product): array => ListProductsItemResponse::create($product)->toArray(),
            $products,
        );
    }
}
